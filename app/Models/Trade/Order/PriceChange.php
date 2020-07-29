<?php


namespace App\Models\Trade\Order;


use App\Consts\Trade\OrderConst;
use App\Consts\Trade\PayConst;
use App\Models\Trade\BaseTrade;

class PriceChange extends BaseTrade
{

    protected $table = 'order_price_change';

    protected $primaryKey = 'price_change_id';

    protected $casts = [
        'order_no' => 'string',
        'water_no' => 'string'
    ];

    public function getByWaterNo($waterNo){
        return $this->where(['water_no'=>$waterNo])->first();
    }

    public function getMainFirstPay($orderNo){
        return $this->where(['order_no'=>$orderNo,'change_type'=>OrderConst::PRICE_CHANGE_ORDER_PAY,'op_state'=>PayConst::STATE_PAY])->first();
    }
}
