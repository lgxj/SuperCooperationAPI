<?php


namespace App\Services\Trade\Fund\Admin;


use App\Bridges\User\UserBridge;
use App\Consts\Trade\WithDrawConst;
use App\Consts\UserConst;
use App\Models\Trade\Fund\WithdrawApply;
use App\Services\ScService;
use App\Services\User\UserService;
use Illuminate\Pagination\LengthAwarePaginator;

class WithDrawManagerService extends ScService
{

    public function search($filter = [], $pageSize = 10, $orderColumn = 'created_at', $direction = 'desc'){
        $withDrawModel = WithdrawApply::getModel();
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

        $withDrawModel = $withDrawModel->when(!empty($userIds), function ($query) use ($userIds) {
            $query->whereIn('user_id', $userIds);
        })->when(!empty($filter['create_time']),function ($query) use($filter){
            $query->where('created_at','>=',$filter['created_time'][0])->where('created_at','<=',$filter['created_time'][1]);
        })->when(!empty($filter['channel_trade_no']),function ($query) use($filter){
            $query->where('channel_trade_no',$filter['channel_trade_no']);
        })->when(!empty($filter['withdraw_no']),function ($query) use($filter){
            $query->where('withdraw_no',$filter['withdraw_no']);
        })->when(!empty($filter['withdraw_account']),function ($query) use($filter){
            $query->where('withdraw_account',$filter['withdraw_account']);
        })->when(isset($filter['status']) && $filter['status'],function ($query) use($filter){
            $query->where('status',$filter['status']);
        });
        $result = $withDrawModel->orderBy($orderColumn, $direction)->paginate($pageSize);
        $withDraws = collect($result->items());
        $userIds = array_unique($withDraws->pluck('user_id')->toArray());
        $users = $userBridge->users($userIds);
        collect($result->items())->map(function ($log) use($users) {
            $user = $users[$log['user_id']] ?? [];
            $log['user_avatar'] = $user['user_avatar'];
            $log['user_name'] = $user['user_name'];
            $log['display_withdraw_money'] = display_price($log['withdraw_money']);
            $log['display_fee_money'] = display_price($log['withdraw_fee_money']);
            $log['display_withdraw_type'] = WithDrawConst::getTypeList($log['withdraw_type']);
            $log['display_transfer_type'] = WithDrawConst::getTransferTypeList($log['transfer_type']);
            $log['display_status'] = WithDrawConst::getStatusList($log['status']);
            $log['withdraw_desc'] = '';
            if($log['withdraw_type'] == WithDrawConst::TYPE_BANK){
                $log['withdraw_desc'] = "余额-转出到银行卡";
            }elseif($log['withdraw_type'] == WithDrawConst::TYPE_ALIPAY){
                $log['withdraw_desc'] = "余额-转出到支付宝";
            }elseif($log['withdraw_type'] == WithDrawConst::TYPE_WEIXIN){
                $log['withdraw_desc'] = "余额-转出到微信";
            }
            return $log;
        });
        return $result;
    }

    /**
     * @return UserService
     */
    protected function getUserBridge(){
        return new UserBridge(new UserService());
    }
}
