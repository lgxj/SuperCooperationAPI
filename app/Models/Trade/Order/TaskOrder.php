<?php


namespace App\Models\Trade\Order;


use App\Models\Trade\BaseTrade;

class TaskOrder extends BaseTrade
{
    protected $table = 'order';

    protected $primaryKey = 'order_id';

    protected $casts = [
        'order_no' => 'string'
    ];
    public function getByOrderNo($orderNo){
        if(empty($orderNo)){
            return null;
        }
       return $this->where('order_no',$orderNo)->first();
    }

    public function getByOrderNos(array $orderNos ){
        if(empty($orderNos)){
            return null;
        }
        return $this->whereIn('order_no',$orderNos)->get()->keyBy('order_no');
    }
}
