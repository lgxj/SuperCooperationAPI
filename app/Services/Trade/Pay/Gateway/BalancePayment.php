<?php


namespace App\Services\Trade\Pay\Gateway;



use App\Bridges\User\UserBridge;
use App\Consts\DBConnection;
use App\Consts\Trade\PayConst;
use App\Consts\Trade\WithDrawConst;
use App\Exceptions\BusinessException;
use App\Models\Trade\Fund\Account;
use App\Models\Trade\Fund\FreezeLog;
use App\Models\Trade\Fund\WithdrawApply;
use App\Models\User\User;
use App\Services\Trade\Fund\AccountService;
use App\Services\User\UserService;
use App\Utils\UniqueNo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BalancePayment
{

    /**
     * @param int $userId
     * @param int $payMoney 单位分
     * @return bool
     * @throws BusinessException
     */
    public function pay(int $userId,int $payMoney){
       if($userId <= 0 ){
           throw new BusinessException("用户信息错误");
       }
       if($payMoney <= 0){
           throw new BusinessException("支付金额错误");
       }
        $connection = DBConnection::getTradeConnection();
        try {
           $connection->beginTransaction();
           $account = Account::where('user_id',$userId)->first();
           if($payMoney > $account['available_balance']){
              throw new BusinessException("余额不足");
           }
           $account->decrement('available_balance',$payMoney);
           $connection->commit();
           return true;
       }catch (\Exception $e){
           \Log::error("余额支付失败 user_id:{$userId} pay_price:{$payMoney}  message:{$e->getMessage()}");
           $connection->rollBack();
           throw new BusinessException($e->getMessage());
       }
    }


    public function add(int $userId,int $payMoney){
        if($userId <= 0 ){
            throw new BusinessException("用户信息错误");
        }
        if($payMoney <= 0){
            throw new BusinessException("金额错误");
        }
        if($payMoney > 250000){
            throw new BusinessException("单次新增余额超过上线2500RMB");
        }
        $accountService = $this->getAccountService();
        $availableBalance = $accountService->getAccountByUserId($userId,'available_balance');
        $connection = DBConnection::getTradeConnection();
        try {
            $connection->beginTransaction();
            $account = Account::where('user_id',$userId)->first();
            $account->increment('available_balance',$payMoney);
            $account->increment('balance',$payMoney);
            $connection->commit();
            return true;
        }catch (\Exception $e){
            \Log::error("新增余额失败 user_id:{$userId} pay_price:{$payMoney} total_price:{$availableBalance} message:{$e->getMessage()}");
            $connection->rollBack();
            throw new BusinessException($e->getMessage());
        }
    }

    /**
     * 余额冻结
     *
     * @param int $userId
     * @param int $freezeMoney
     * @param int $freezeType
     * @param int $businessNo 业务编号，如提现流水号
     * @return bool
     * @throws BusinessException
     */
    public function freeze($userId,$freezeMoney,$freezeType,$businessNo){
        if($userId <= 0 ){
            throw new BusinessException("用户信息错误");
        }
        if($freezeMoney <= 0){
            throw new BusinessException("冻结金额错误");
        }
        $channel = '';
        if($freezeType == WithDrawConst::FREEZE_TYPE_WITHDRAW){
            $channel = PayConst::CHANNEL_BALANCE;
        }
        $connection = DBConnection::getTradeConnection();
        try {
            $freeLogModel = $this->getFreezeLog();
            $connection->beginTransaction();
            $account = Account::where('user_id',$userId)->first();
            if($freezeMoney > $account['available_balance']){
                throw new BusinessException("余额不足");
            }
            $account->decrement('available_balance',$freezeMoney);
            $account->increment('freeze',$freezeMoney);

            $freeLogModel->water_no = UniqueNo::buildFreezeNo($userId,$freezeType);
            $freeLogModel->user_id = $userId;
            $freeLogModel->freeze = $freezeMoney;
            $freeLogModel->balance = $account->available_balance;
            $freeLogModel->target_id =  $businessNo;
            $freeLogModel->freeze_type = $freezeType;
            $freeLogModel->channel = $channel;
            $freeLogModel->freeze_state = WithDrawConst::FREEZE_STATUS_DOING;
            $freeLogModel->save();
            $connection->commit();
            return true;
        }catch (\Exception $e){
            \Log::error("余额冻结失败 user_id:{$userId} freeze_price:{$freezeMoney}  message:{$e->getMessage()}");
            $connection->rollBack();
            throw new BusinessException($e->getMessage());
        }
    }


    /**
     * 提现解冻
     *
     * @param $userId
     * @param $withDrawNo
     * @param bool $isReturnAccount 提现失败，返回到余额账号
     * @return bool
     * @throws BusinessException
     */
    public function unFreezeWithDraw($userId,$withDrawNo,$isReturnAccount = false){
        if($userId <= 0 ){
            throw new BusinessException("用户信息错误");
        }
        if(empty($withDrawNo)){
            throw new BusinessException("提现流水号不存在");
        }
        $withDraw = WithdrawApply::where(['user_id'=>$userId,'withdraw_no'=>$withDrawNo])->first();
        $freezeLog = FreezeLog::where(['user_id'=>$userId,'target_id'=>$withDrawNo,'freeze_type'=>WithDrawConst::FREEZE_TYPE_WITHDRAW])->first();
        if(empty($withDraw)){
            throw new BusinessException('提现记录不存在');
        }
        if(empty($freezeLog)){
            throw new BusinessException('冻结记录不存在');
        }
        if($withDraw['withdraw_money'] != $freezeLog['freeze']){
            throw new BusinessException('解冻金额异常'.$withDrawNo);
        }
        $connection = DBConnection::getTradeConnection();
        try {
            $freeLogModel = $this->getFreezeLog();
            $connection->beginTransaction();
            $account = Account::where('user_id',$userId)->first();
            if($freezeLog['freeze'] > $account['freeze']){
                throw new BusinessException("冻结余额不足");
            }
            $account->decrement('freeze',$freezeLog['freeze']);
            if($isReturnAccount){
                $account->increment('available_balance',$withDraw['withdraw_money']);
            }
            $freezeLog->un_freeze_time = Carbon::now();
            $freezeLog->freeze_state = WithDrawConst::FREEZE_STATUS_REVERSE;
            $freeLogModel->save();
            $connection->commit();
            return true;
        }catch (\Exception $e){
            \Log::error("余额解冻失败 user_id:{$userId} freeze_price:{$freezeLog['freeze']}  message:{$e->getMessage()}");
            $connection->rollBack();
            throw new BusinessException($e->getMessage());
        }
    }
    /**
     * 验证支付密码
     *
     * @param int $userId
     * @param string $payPassword
     * @return bool
     * @throws BusinessException
     */
    public function verifyPayPassword(int $userId, string $payPassword) : bool
    {
        if($userId <= 0){
            throw new BusinessException("您还没有登录");
        }
        if(empty($payPassword)){
            throw new BusinessException("请输入支付密码");
        }
        $payPassword = trim($payPassword);
        $userModel = new User();
        $user = $userModel->find($userId);
        if(!$user){
            throw new BusinessException("您还没有注册，请先注册");
        }
        if(!$user->pay_password){
            throw new BusinessException("您还没有设置支付密码，请先设支付密码");
        }
        $userService = $this->getUserService();
        if($user->pay_password !== $userService->buildPassword($payPassword, $user->register_salt)){
            return false;
        }
        return true;
    }

    public function getAccountService(){
        return new AccountService();
    }

    /**
     * @return UserService
     */
    public function getUserService(){
        return new  UserBridge(new UserService());
    }

    public function getFreezeLog(){
        return new FreezeLog();
    }
}
