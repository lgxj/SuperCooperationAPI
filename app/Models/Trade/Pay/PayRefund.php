<?php


namespace App\Models\Trade\Pay;


use App\Consts\Trade\PayConst;
use App\Models\Trade\BaseTrade;

class PayRefund extends BaseTrade
{
    protected $table = 'pay_refund';

    protected $primaryKey = 'refund_id';

    protected $casts = [
        'pay_no' => 'string',
        'refund_no' => 'string',
        'biz_no' => 'string',
        'biz_sub_no' => 'string'
    ];

    public function getAllRefundLogByBizNo($bizNo, $userId = null){
        return PayRefund::where('biz_no', $bizNo)->when($userId, function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->where('state', PayConst::REFUND_STATE_YES)->orderByDesc('refund_price')->get();
    }
}
