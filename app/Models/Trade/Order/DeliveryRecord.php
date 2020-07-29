<?php


namespace App\Models\User;


use App\Consts\Trade\OrderConst;
use App\Exceptions\BusinessException;
use App\Models\Trade\BaseTrade;
use Carbon\Carbon;

class DeliveryRecord extends BaseTrade
{
    protected $table = 'order_delivery_record';

    protected $primaryKey = 'delivery_id';

    protected $casts = [
        'order_no' => 'string'
    ];

    public function getLatestRecord($userId,$orderNo){
        if($userId <= 0 || empty($orderNo)){
            return null;
        }
        return $this->where(['user_id'=>$userId,'order_no'=>$orderNo])->orderBy('created_at')->first();
    }

    public function refuseRecord($userId,$orderNo,$refuseType = OrderConst::REFUSE_TYPE_EMPLOYER_LEAVE,$refuseReason = ''){
        $first = $this->getLatestRecord($userId,$orderNo);
        if(empty($first)){
            return false;
        }
        $first->refuse_time = Carbon::now();
        $first->refuse_type =$refuseType;
        $first->refuse_reason =$refuseReason;
        $first->update();
        return true;
    }

    public function add($orderNo,$userId)
    {
        if(empty($orderNo) || $userId <= 0){
            throw new BusinessException("参数错误");
        }
        $this->order_no = $orderNo;
        $this->user_id = $userId;
        $this->save();
        return $this->delivery_id ?? 0;
    }
}
