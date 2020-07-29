<?php


namespace App\Models\Trade\Order;


use App\Consts\Trade\OrderConst;
use App\Models\Trade\BaseTrade;

class ReceiverOrder extends BaseTrade
{
    protected $table = 'order_receiver';

    protected $primaryKey = 'receiver_id';

    protected $casts = [
        'order_no' => 'string'
    ];
    public function getOrderQuotedList($orderNo){
        return $this->where(['order_no'=>$orderNo,'receive_state'=>OrderConst::HELPER_STATE_EMPLOYER_UN_CONFIRM])->orderByDesc('quoted_price')->get();
    }

    public function getOrderAllReceiverList($orderNo){
        return $this->where(['order_no'=>$orderNo])->whereIn('receive_state',[OrderConst::HELPER_STATE_EMPLOYER_UN_CONFIRM,OrderConst::HELPER_STATE_RECEIVE,OrderConst::HELPER_STATE_DELIVERED])->orderByDesc('quoted_price')->get();
    }

    public function getOrderHelper($orderNo,$helperUserId){
        return $this->where(['order_no'=>$orderNo,'user_id'=>$helperUserId])->first();
    }

    public function getValidReceiver($orderNo){
        return $this->where(['order_no'=>$orderNo,'is_selected'=>1])->first();
    }

    public function getValidReceiverByOrderNos(array $orderNos){
        return $this->where(['is_selected'=>1])->whereIn('order_no',$orderNos)->get();
    }

    public function totalDoingTaskOrder($userId){
        return $this->where(['user_id'=>$userId])->whereIn('receive_state',[OrderConst::HELPER_STATE_RECEIVE])->count();
    }

    public function getCompensateOrder($userId,$compensateStatus = OrderConst::CANCEL_COMPENSATE_STATUS_HAS){
        return $this->where(['user_id'=>$userId,'cancel_compensate_status'=>$compensateStatus])->first();
    }
}
