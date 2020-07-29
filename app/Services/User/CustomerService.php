<?php
namespace App\Services\User;

use App\Bridges\Message\IMUserBridge;
use App\Bridges\Permission\AdminBridge;
use App\Consts\CacheConst;
use App\Consts\GlobalConst;
use App\Consts\UserConst;
use App\Exceptions\BusinessException;
use App\Models\Message\Im\MessageImPrimary;
use App\Models\User\User;
use App\Models\User\UserCustomer;
use App\Services\Message\IMUserService;
use App\Services\Permission\AdminService;
use App\Services\ScService;
use Illuminate\Support\Facades\Cache;

class CustomerService extends ScService
{
    private $model;

    /**
     * @var IMUserService
     */
    private $imUserService;

    /**
     * @var AdminService
     */
    private $adminService;

    private $userModel;

    public function __construct(UserCustomer $model, IMUserBridge $IMUserBridge, AdminBridge $adminBridge, User $user)
    {
        $this->model = $model;
        $this->imUserService = $IMUserBridge;
        $this->adminService = $adminBridge;
        $this->userModel = $user;
    }

    /**
     * 客服列表(前端)
     * @param int $systemId
     * @param int $subId
     * @param array $fields
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getCustomerService($systemId = 0, $subId = 0, $fields = ['user_id','identifier', 'nick', 'image'])
    {
        $userIds = $this->model->where('system_id', $systemId)->where('sub_id', $subId)->where('state',UserCustomer::STATE_NORMAL)->select(['user_id'])->pluck('user_id');
        $userList = $this->userModel->whereIn('user_id',$userIds)->select(['user_name','user_id'])->get()->keyBy('user_id')->toArray();

        $list = [];
        collect($this->imUserService->getByUserIds($userIds, $fields)->toArray())->each(function (&$item) use ($userList,&$list){
            $info = $userList[$item['user_id']] ?? [];
            $item['user_id'] = $info['user_id'];
            $item['user_name'] = $info['user_name'];
            $list[] = $item;
        });
        return $list;
    }

    /**
     * 搜索可添加为客服的用户
     * @param $username
     * @param $systemId
     * @param $subId
     * @return array
     * @throws \Exception
     */
    public function searchAbleAddCustomer($username, $systemId = 0, $subId = 0)
    {
        $user = (new UserService())->findByGrantType(UserConst::GRANT_LOGIN_TYPE_PHONE, $username);
        if (!$user) {
            throw new BusinessException('未找到用户');
        }
        // 设为客服必须先加为管理用户
        $admin = $this->adminService->getByUserId($user['user_id'], $systemId, $subId);
        if (!$admin) {
            throw new BusinessException('此账号不是管理用户，请先到权限管理中将些账号添加为管理用户');
        }
        // 是否已是客服
        $customer = $this->model->where('user_id', $user['user_id'])->where('system_id', $systemId)->where('sub_id', $subId)->where('state', UserCustomer::STATE_NORMAL)->first();
        if ($customer) {
            throw new BusinessException('此用户已是客服，请勿重复添加');
        }
        // 是否已绑定IM
        $imUser = $this->imUserService->findByUserId($user['user_id']);
        if (!$imUser) {
            throw new BusinessException('此用户未绑定IM，请先让用户在APP或管理后台登录');
        }

        $userInfo =  (new UserService)->user($user['user_id']);
        if (!$userInfo) {
            throw new BusinessException('用户未找到');
        }
        if (!$userInfo['user_status']) {
            throw new BusinessException('帐号已被冻结');
        }

        return [
            'user_name' => $userInfo['user_name'],
            'nick' => $imUser['nick'],
            'user_id' => $user['user_id']
        ];
    }

    /**
     * 添加客服账号
     * @param $userId
     * @param $systemId
     * @param $subId
     * @return bool
     * @throws BusinessException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function addCustomer($userId, $systemId = 0, $subId = 0)
    {
        // 设为客服必须先加为管理用户
        $admin = $this->adminService->getByUserId($userId, $systemId, $subId);
        if (!$admin) {
            throw new BusinessException('此账号不是管理用户，请先到权限管理中将些账号添加为管理用户');
        }
        // 是否已是客服
        $customer = $this->model->where('user_id', $userId)->where('system_id', $systemId)->where('sub_id', $subId)->first();
        if ($customer && $customer->state == UserCustomer::STATE_NORMAL) {
            throw new BusinessException('此用户已是客服，请勿重复添加');
        }
        // 是否已绑定IM
        $imUser = $this->imUserService->findByUserId($userId);
        if (!$imUser) {
            throw new BusinessException('此用户未绑定IM，请先让用户在APP或管理后台登录');
        }

        if ($customer) {
            $customer->state = UserCustomer::STATE_NORMAL;
            $result = $customer->save();
        } else {
            $data = [
                'user_id' => $userId,
                'system_id' => $systemId,
                'sub_id' => $subId,
                'state' => UserCustomer::STATE_NORMAL
            ];
            $result = $this->model->insert($data);
        }

        if ($result) {
            $cacheKey = CacheConst::CUSTOMER_USER_IDS . $systemId . $subId;
            $customerUserIds = Cache::get($cacheKey) ?: [];
            $customerUserIds[] = $userId;
            Cache::set($cacheKey, array_unique($customerUserIds));
        }
        return $result;
    }

    /**
     * 取消客服账号
     * @param $userId
     * @param $systemId
     * @param $subId
     * @return int
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function cancelCustomer($userId, $systemId = 0, $subId = 0)
    {
        $result = $this->model->where('user_id', $userId)->where('system_id', $systemId)->where('sub_id', $subId)->update(['state' => UserCustomer::STATE_DISABLED]);
        if ($result) {
            $cacheKey = CacheConst::CUSTOMER_USER_IDS . $systemId . $subId;
            $customerUserIds = Cache::get($cacheKey) ?: [];
            $customerUserIds = array_diff($customerUserIds, [$userId]);
            Cache::set($cacheKey, array_unique($customerUserIds));
        }
        return $result;
    }

    /**
     * 获取客服，优先返回接代过的客服
     * @param $userId
     * @param int $systemId
     * @param int $subId
     * @return int
     */
    public function getCurrentCustomer($userId, $systemId = 0, $subId = 0){
        $cacheKey = CacheConst::CUSTOMER_USER_IDS . $systemId . $subId;
        $customerUserIds = \Cache::get($cacheKey);
        if(empty($customerUserIds)){
            return GlobalConst::DEFAULT_CUSTOMER_ID;
        }
        $customer = MessageImPrimary::where(['send_user_id'=>$userId])->whereIn('to_user_id',$customerUserIds)->get()->first();//用户找客服
        if(empty($customer)){
            $customer = MessageImPrimary::where(['to_user_id'=>$userId])->whereIn('send_user_id',$customerUserIds)->get()->first();
        }
        if(empty($customer)){
            return $customerUserIds[array_rand($customerUserIds)];
        }
        if($customer->send_user_id == $userId){
            return $customer->to_user_id;
        }else{
            return $customer->send_user_id;
        }
    }
}
