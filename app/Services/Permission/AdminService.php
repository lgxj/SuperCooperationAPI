<?php

namespace App\Services\Permission;

use App\Bridges\Permission\AdminLogBridge;
use App\Bridges\Permission\RoleBridge;
use App\Bridges\User\UserBridge;
use App\Consts\DBConnection;
use App\Consts\ErrorCode\UserErrorCode;
use App\Consts\GlobalConst;
use App\Consts\PermissionConst;
use App\Consts\UserConst;
use App\Exceptions\BusinessException;
use App\Models\Permission\AdminRole;
use App\Models\Permission\Resource;
use App\Models\User\User;
use App\Models\User\UserGrantLogin;
use App\Models\User\UserLoginRecord;
use App\Services\User\UserService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AdminService extends BasePermissionService
{


    /**
     * @var AdminLogService
     */
    protected $adminLogBridge;

    /**
     * @var UserService
     */
    protected $userBridge;


    public function __construct()
    {
        $this->adminLogBridge = new AdminLogBridge(new AdminLogService());
        $this->userBridge = new UserBridge(new UserService());
    }

    /**
     * 用户登录
     * @param $phone
     * @param $password
     * @param $loginFrom
     * @return array
     * @throws BusinessException
     */
    public function login($phone,$password,$loginFrom)
    {
        $password = trim($password);
        $phone = trim($phone);
        $grantModel = new UserGrantLogin();
        /** @var UserGrantLogin $userGrant */
        $userGrant = $grantModel->findByGrantType(UserConst::GRANT_LOGIN_TYPE_PHONE,$phone);
        if(empty($userGrant)){
            throw new BusinessException("您还没有注册，请先注册",UserErrorCode::INFO_PHONE_NOT_EXIST);
        }
        $userId = $userGrant->user_id;
        $user = User::find($userId);
        $salt = $user->register_salt;
        if($userGrant->grant_login_credential !== $this->buildPassword($password,$salt)){
            throw new BusinessException("账户和密码错误！",UserErrorCode::LOGIN_PASSWORD_ERROR);
        }
        return $this->setLogin($user,$userGrant,$phone,$loginFrom);
    }

    public function buildPassword($password,$salt){
        return  sha1(md5($password).$salt);
    }

    protected function buildFixedKey($userId,$salt,$loginFrom = GlobalConst::CLIENT_H5_USER){
        return 'login'.$userId.$salt.$loginFrom;
    }

    protected function setLogin(User $user,UserGrantLogin $grantLogin,$phone = '',$loginFrom = GlobalConst::CLIENT_H5_USER){

        $cacheData = [
            'user_id'=>$grantLogin->user_id,
            'phone'=>$phone,
            'salt'=>$user->register_salt,
            'grant_type'=>$grantLogin->grant_login_type,
            'grant_identify'=>$grantLogin->grant_login_identify,
            'grant_login_id'=> $grantLogin->grant_login_id,
            'is_certification' => $user['is_certification'] ?? 0
        ];
        $expireHours = env('LOGIN_EXPIRE_HOUR',48);
        $expiredDate = now()->addHours($expireHours);
        $fixedKey = $this->buildFixedKey($grantLogin->user_id,$user->register_salt,$loginFrom);
        $frontToken = md5($fixedKey.quick_random(8));//返回给前端的加蜜登录token
        $preToken = Cache::get($fixedKey);
        if($preToken) {
            Cache::delete($preToken);
        }
        Cache::put($fixedKey,$frontToken,$expiredDate);
        Cache::put($frontToken,$cacheData,$expiredDate);
        $cacheData['token'] = $frontToken;
        $ip = ip2long(request()->ip());
        $user->last_login_ip = $ip;
        $user->last_login_at = Carbon::now();
        $user->save();
        $this->saveUserLoginRecord($grantLogin->user_id,$grantLogin->grant_login_type,$loginFrom,$user['last_login_ip']);
        return $cacheData;
    }

    protected function saveUserLoginRecord($userId,$grantLoginType,$loginFrom,$ip){
        $loginRecord = new UserLoginRecord();
        $loginRecord->user_id = $userId;
        $loginRecord->grant_login_type = $grantLoginType;
        $loginRecord->login_from = $loginFrom;
        $loginRecord->login_ip = $ip;
        $loginRecord->save();
    }


    /**
     * 获取管理员信息
     * @param int $userId
     * @param $systemId
     * @param int $subId
     * @return UserService|UserService[]|array|\Illuminate\Database\Eloquent\Collection|Model|null
     * @throws BusinessException
     */
    public function getInfo(int $userId, $systemId = PermissionConst::SYSTEM_MANAGE, $subId = 0)
    {
        $subId = $subId ?: 0;

        /** @var RoleService $roleBridge */
        $roleBridge = new RoleBridge(new RoleService());
        $admin = $this->userBridge->get($userId);

        // 判断是否是管理员
        if ($systemId == PermissionConst::SYSTEM_MANAGE && !$roleBridge->isSystemUser($admin['user_id'], $systemId)) {
            throw new BusinessException('此帐号不是管理员', UserErrorCode::NOT_ADMIN);
        }

        if (!$subId) {
            $subIds = $roleBridge->getUserSubs($admin['user_id'], $systemId);
            $subId = $subIds[0] ?? 0;
        }

        $userRoleIds = $roleBridge->getUserSubRoles($admin['user_id'], $systemId, $subId);

        $admin['is_super'] = false;
        if (count($userRoleIds) == 1 && $userRoleIds[0] == 0) {
            $admin['is_super'] = true;
            $admin['roles_arr'] = [
                ['name' => '超级管理员', 'remark' => '拥有所有权限']
            ];
            $admin['permissions'] = (new Resource)->where('system_id', $systemId)->where('status', 1)->where('is_dev', 0)->pluck('code')->toArray();
        } else {
            $admin['roles_arr'] = $roleBridge->getByIds($userRoleIds);
            $permissions = $roleBridge->getResourceIds($userId,PermissionConst::SYSTEM_MANAGE,0);
            $admin['permissions'] = $permissions;
        }
        $admin['roles'] = ['admin'];
        $admin['sub_id'] = $subId ?: 0;

        return $admin;
    }

    /**
     * 指定管理用户信息
     * @param $userId
     * @param $systemId
     * @param int $subId
     * @return UserService|UserService[]|array|\Illuminate\Database\Eloquent\Collection|Model|null
     */
    public function getDetail($userId, $systemId, $subId = 0)
    {
        $admin = $this->userBridge->get($userId);

        $where = [
            'system_id' => $systemId,
            'sub_id' => $subId,
            'user_id' => $userId
        ];
        $userRoles = (new AdminRole)->where($where)
            ->orderByDesc('admin_role_id')
            ->get();

        // 手机号信息
        $phones = $this->userBridge->getPhoneByUserIds([$userId]);

        $admin['phone'] = $phones[$userId] ?? '';
        $admin['roles'] = $userRoles;

        $admin['is_super'] = ($userRoles[0]['role_id'] ?? false) === 0;

        return $admin;
    }

    /**
     * 退出登录
     * @param string $token
     * @return bool
     * @throws BusinessException
     */
    public function logout($token)
    {
        $this->userBridge->loginOut($token);
        return true;
    }

    /**
     * 添加管理员
     * @param string $phone 手机号
     * @param string $password 密码
     * @param string $avatar 头像
     * @param string $name 姓名
     * @param array $roleIds 角色
     * @param int $systemId 所属系统
     * @param int $subId 所属业务
     * @return array
     * @throws BusinessException
     */
    public function add($phone, $password, $avatar, $name, $systemId, $subId,array $roleIds = [])
    {

        $userGrant = $this->userBridge->findByGrantType(UserConst::GRANT_LOGIN_TYPE_PHONE,$phone);
        $connection = DBConnection::getPermissionConnection();
        try {
            $connection->beginTransaction();
            if(!$userGrant) {
                $register = $this->userBridge->registerByPhone($phone, 7842,$password,$password,GlobalConst::CLIENT_WEB_ADMIN,false);
                $userId = $register['user_id'];
            }else{
                $userRole = AdminRole::where('system_id', $systemId)->where('sub_id', $subId)->where('user_id', $userGrant['user_id'])->first();
                if ($userRole) {
                    throw new BusinessException('手机号已被添加，请勿重复添加');
                }
                $userId = $userGrant['user_id'];
            }

            if($userId <= 0){
                throw  new BusinessException("添加管理员失败");
            }
            $user = $this->userBridge->updateUserBaseInfo($userId,$name,$avatar);
            if($roleIds) {
                // 保存角色关联
                $roles = [];
                foreach ($roleIds as $roleId) {
                    $roles[$roleId] = [
                        'role_id' => $roleId,
                        'system_id' => $systemId,
                        'sub_id' => $subId,
                        'user_id' => $userId
                    ];
                }
                (new AdminRole)->insert($roles);
            }
            // 记录日志
            $this->adminLogBridge->create('admin-add', '添加管理员', '手机号：' . $phone . '；姓名：' . $name);
            $connection->commit();
            return $user;
        } catch (\Exception $e) {
            $connection->rollBack();
            \Log::error('添加管理员失败:'  . PHP_EOL . ' message: ' . $e->getMessage());
            throw new BusinessException($e->getMessage());
        }
    }

    /**
     * 编辑管理员信息
     * @param int $userId
     * @param string $name
     * @param string $avatar
     * @param int $systemId
     * @param int $subId
     * @param array $roleIds
     * @return bool
     * @throws BusinessException
     */
    public function edit(int $userId,string $name, string $avatar, $systemId, $subId,array $roleIds)
    {


        $admin = $this->userBridge->get($userId);
        if (!$admin) {
            throw new BusinessException('管理员信息未找到');
        }
        $connection = DBConnection::getPermissionConnection();
        try {
            $connection->beginTransaction();
            $name = trim($name) ? $name : $admin['user_name'];
            $avatar = trim($avatar) ? $avatar : $admin['user_avatar'];
            $this->userBridge->updateUserBaseInfo($admin['user_id'], $name , $avatar);
            if($roleIds) {
                // 更新角色关联
                $roles = [];
                foreach ($roleIds as $roleId) {
                    $roles[$roleId] = [
                        'role_id' => $roleId,
                        'system_id' => $systemId,
                        'sub_id' => $subId,
                        'user_id' => $userId
                    ];
                }
                (new AdminRole)->where('system_id', $systemId)->where('sub_id', $subId)->where('user_id', $userId)->delete();
                (new AdminRole)->insert($roles);
            }

            // 记录日志
            $this->adminLogBridge->create('admin-edit', '修改管理员', '');
            $connection->commit();
            return true;
        } catch (\Exception $e) {
            $connection->rollBack();
            \Log::error('编辑管理员信息失败:' . PHP_EOL . ' message: ' . $e->getMessage());
            throw new BusinessException('编辑失败');
        }
    }

    /**
     * 管理员列表
     * @param array $filter
     * @param int $page
     * @param int $pageSize
     * @param int $systemId
     * @param int $subId
     * @return array
     */
    public function getList($filter = [], $page = 1, $pageSize = 10, $systemId = 0, $subId = 0)
    {
        $where = [
            'system_id' => $systemId,
            'sub_id' => $subId
        ];
        $userRoles = (new AdminRole)->where($where)
            ->when(isset($filter['role_id']) && $filter['role_id'] !== '', function ($query) use ($filter) {
                $query->where('role_id', $filter['role_id']);
            })
            ->orderByDesc('admin_role_id')
            ->get();

        $userIds = $userRoles->pluck('user_id')->unique();
        $total = $userIds->count();

        // 分页用户信息
        $userIds = $userIds->slice(($page - 1) * $pageSize, $pageSize)->all();
        $users = $this->userBridge->users($userIds);

        // 手机号信息
        $phones = $this->userBridge->getPhoneByUserIds($userIds);

        // 按角色搜索时，需要重新查询用户拥有角色
        if (!empty($filter['role_id'])) {
            $userRoleIds = (new AdminRole)->where($where)->whereIn('user_id', $userIds)->select(['role_id', 'user_id'])->get()->groupBy('user_id');
        } else {
            $userRoleIds = $userRoles->groupBy('user_id')->all();
        }

        $list = collect($users)->map(function ($item) use ($userRoleIds, $phones) {
            $item['last_login_ip'] = $item['last_login_ip'] ? long2ip($item['last_login_ip']) : '';
            $roleIds = $userRoleIds[$item['user_id']] ?? [];
            $role = collect($roleIds)->pluck('role_id')->unique()->all();
            $item['is_super'] = ($role[0] ?? false) === 0;
            $item['roleIds'] = $role;
            $item['phone'] = $phones[$item['user_id']] ?? '';
            $item['admin_role_id'] = $roleIds[0]['admin_role_id'] ?? 0;
            $item['is_self'] = $item['user_id'] == request('admin.user_id');
            return $item;
        })->sortByDesc('user_id')->values()->all();

        return [
            'total' => $total,
            'list' => $list
        ];
    }

    /**
     * 重置密码
     * @param int $userId
     * @param int  $password
     * @param int $systemId
     * @param int $subId
     * @return bool
     * @throws BusinessException
     */
    public function resetPwd($userId, $password,$systemId,$subId)
    {
        $data = [
            'user_id' => $userId,
            'password' => $password,
        ];
        $validate = \Validator::make($data, [
            'user_id' => 'required',
            'password' => 'required',
        ], [
            'user_id.required' => 'ID错误',
            'password.required' => '请输入密码'
        ]);
        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first());
        }

        try {
            $user = $this->userBridge->findByUserPhone($userId);
            if(empty($user)){
                throw new BusinessException('操作权限错误');
            }

            $roles = (new RoleService())->getUserSubRoles($userId,$systemId,$subId);
            if(empty($roles)){
                throw new BusinessException("您不能修改普通用户密码");
            }
            $this->userBridge->resetPassword($user['grant_login_identify'],1111,$password,$password,'',false);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 修改自己密码
     * @param $pwdOld
     * @param $pwd
     * @param $userId
     * @return bool
     * @throws BusinessException
     */
    public function resetSelfPwd($pwdOld, $pwd, $userId)
    {
        return $this->getUserService()->resetPwdByOldPwd($pwdOld, $pwd, $userId);
    }

    /**
     * 根据userId查找管理用户
     * @param $userId
     * @param int $systemId
     * @param int $subId
     * @param bool $isRole
     * @return Model
     */
    public function getByUserId($userId, $systemId = 0, $subId = 0,$isRole = true)
    {
        $admin = $this->userBridge->get($userId);
        if (!$admin) {
            return null;
        }
        if ($subId && $isRole) {
            $role = (new AdminRole)->where('admin_id', $admin->admin_id)->where('sub_id', $subId)->where('system_id', $systemId)->first();
            if (!$role) {
                return null;
            }
        }
        return $admin;
    }


    public function delManager($userId,$systemId = 0, $subId = 0){
        if($userId <= 0 || $systemId <= 0){
            throw new BusinessException('参数错误');
        }
        $roleService = new RoleService();
        $roleIds = $roleService->getUserSubRoles($userId,$systemId,$subId);
        if(empty($roleIds)){
            return true;
        }
        if(in_array(PermissionConst::CREATOR_ROLE_ID,$roleIds)){
            throw new BusinessException('创始团队/创建人不能删除');
        }
        AdminRole::getModel()->where(['user_id'=>$userId,'system_id'=>$systemId,'sub_id'=>$subId])->delete();
        $this->clearUserCache($systemId,$subId);
        return true;
    }


    /**
     * 根据手机号查询用户基础信息
     * @param $phone
     * @return array
     */
    public function getBaseByPhone($phone)
    {
        $userService = $this->getUserService();
        $res = $userService->findByGrantType(UserConst::GRANT_LOGIN_TYPE_PHONE, $phone);
        if (!$res) {
            return [];
        }

        $user = $userService->user($res['user_id']);
        return [
            'user_id' => $res['user_id'],
            'user_name' => $user['user_name'],
            'username' => $phone,
            'user_avatar' => $user['user_avatar']
        ];
    }

}
