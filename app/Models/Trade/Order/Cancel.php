<?php


namespace App\Models\Trade\Order;


use App\Consts\UserConst;
use App\Models\Trade\BaseTrade;

class Cancel extends BaseTrade
{
    protected $table = 'order_cancel';

    protected $primaryKey = 'cancel_id';

    protected $casts = [
        'order_no' => 'string'
    ];

    public function getHelperCancelLatest($orderNo)
    {
        return $this->where(['order_no'=>$orderNo,'user_type'=>UserConst::TYPE_HELPER])->first();
    }
}
