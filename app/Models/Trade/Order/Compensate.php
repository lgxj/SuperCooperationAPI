<?php


namespace App\Models\Trade\Order;


use App\Models\Trade\BaseTrade;

class Compensate extends BaseTrade
{
    protected $table = 'order_compensate';

    protected $primaryKey = 'compensate_id';

    protected $casts = [
        'order_no' => 'string',
        'pay_no' => 'string'
    ];
    public function getUserCompensateByToUserId($orderNo,$toUserId,$type)
    {
        if (empty($orderNo) || $toUserId <= 0) {
            return [];
        }
        return$this->where(['order_no'=>$orderNo,'compensate_type'=>$type,'to_user_id'=>$toUserId])->first();
    }

    public function getUserCompensateByUserId($orderNo,$userId,$type)
    {
        if (empty($orderNo) || $userId <= 0) {
            return [];
        }
        return$this->where(['order_no'=>$orderNo,'compensate_type'=>$type,'user_id'=>$userId])->first();
    }

    public function getUserCompensateByPayNo($payNo,$userId,$type)
    {
        if (empty($payNo) || $userId <= 0) {
            return [];
        }
        return$this->where(['pay_no'=>$payNo,'compensate_type'=>$type,'user_id'=>$userId])->first();
    }
}
