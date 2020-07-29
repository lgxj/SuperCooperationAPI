<?php


namespace App\Services\Trade\Fund;


use App\Bridges\User\UserBridge;
use App\Consts\Trade\PayConst;
use App\Consts\UserConst;
use App\Exceptions\BusinessException;
use App\Models\Trade\Fund\Account;
use App\Models\Trade\Fund\AccountChange;
use App\Services\ScService;
use App\Services\Trade\Pay\Gateway\BalancePayment;
use App\Services\User\UserService;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 账户与余额服务层
 *
 * Class AccountService
 * @package App\Services\Trade\Fund
 */
class AccountService extends ScService
{

    public  function getAccountByUserId($userId,$accountValue = null){
        if($userId <= 0){
            return [];
        }
        $account = Account::where('user_id',$userId)->first();
        if(empty($account)){
           $account = new Account();
           $account->user_id = $userId;
           $account->balance = 0;
           $account->available_balance = 0;
           $account->freeze = 0;
           $account->cash = 0;
           $account->settled = 0;
           $account->state = 1;
           $account->save();
        }
        $data = $account->toArray();
        return $accountValue ? $data[$accountValue] ?? 0 : $data;
    }

    /**
     * @param $userId
     * @param int $money 以分为单位
     * @param int $adminId
     * @return int
     * @throws BusinessException
     */
    public function addBalance($userId,$money,$adminId = 0){
        if($userId <= 0 || $money <= 0 || $adminId <= 0){
            return 0;
        }
        $changeType = PayConst::ACCOUNT_CHANGE_ADMIN_ADD;
        $userMoney = AccountChange::getModel()->where(['user_id'=>$userId,'change_type'=>$changeType])->sum('money');
        if($userMoney > 200000){
            throw new BusinessException("您为此用户累计添加2000RMB，超过限额");
        }

        $userMoney = AccountChange::getModel()->where(['op_user_id'=>$userId,'change_type'=>$changeType])->sum('money');
        if($userMoney > 1000000){
            throw new BusinessException("您已累计操作10000RMB，超过限额");
        }
        $balancePayment = new BalancePayment();
        $flag = $balancePayment->add($userId,$money);
        if($flag){
            $this->addAccountChange($userId,$money,PayConst::ACCOUNT_CHANGE_ADMIN_ADD,$adminId);
            $this->getInoutLogService()->addInoutLog($userId,$money,PayConst::CHANNEL_BALANCE,PayConst::INOUT_BALANCE_ADMIN,PayConst::SOURCE_RECHARGE,$adminId,$userId);
        }
        return $money;
    }

    public function addAccountChange($userId,$money,$type = PayConst::ACCOUNT_CHANGE_ADMIN_ADD,$opUserId=0){
        if($userId <= 0 || $money <= 0){
            return false;
        }
        $accountChange = new AccountChange();
        $accountChange->user_id = $userId;
        $accountChange->money = $money;
        $accountChange->change_type = $type;
        $accountChange->op_user_id = $opUserId;
        $accountChange->save();
        return true;
    }

    public function search($filter = [], $pageSize = 10, $orderColumn = 'created_at', $direction = 'desc')
    {
        /** @var Account $accountModel */
        $accountModel = Account::getModel();
        $userIds = [];
        $userBridge = $this->getUserBridge();
        if(!empty($filter['user_name'])){
            $userGrant = $userBridge->findByGrantType(UserConst::GRANT_LOGIN_TYPE_PHONE,$filter['user_name']);
            if($userGrant){
                $userIds[] = $userGrant['user_id'];
            }
            if (empty($userIds)) {
                $users = $userBridge->search(['user_name'=>$filter['user_name']]);
                $userIds = array_keys($users);
            }

            if(empty($userIds)){
                return new LengthAwarePaginator([],0,$pageSize);
            }
        }

        $accountModel = $accountModel->when(!empty($userIds), function ($query) use ($userIds) {
            $query->whereIn('user_id', $userIds);
        });

        $result = $accountModel->orderBy($orderColumn, $direction)->paginate($pageSize);
        $tasks = collect($result->items());
        $userIds = array_unique($tasks->pluck('user_id')->toArray());
        $users = $userBridge->users($userIds);

        collect($result->items())->map(function ($item) use($users) {
            $user = $users[$item['user_id']] ?? [];
            $item['user_avatar'] = $user['user_avatar'];
            $item['user_name'] = $user['user_name'];
            $item['display_balance'] = display_price($item['balance']);
            $item['display_available_balance'] = display_price($item['available_balance']);
            $item['display_debt'] = display_price($item['debt']);
            $item['display_freeze'] =  display_price($item['freeze']);
            $item['display_cash'] =  display_price($item['cash']);
            $item['display_settled'] =  display_price($item['settled']);
            return $item;
        });
        return $result;
    }

    /**
     * @return UserService
     */
    protected function getUserBridge(){
        return new UserBridge(new UserService());
    }

    protected function getInoutLogService(){
        return new InoutLogService();
    }
}
