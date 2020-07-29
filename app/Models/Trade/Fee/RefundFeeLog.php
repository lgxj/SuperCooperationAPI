<?php


namespace App\Models\Trade\Fee;


use App\Models\Trade\BaseTrade;

class RefundFeeLog extends BaseTrade
{
    protected $table = 'fund_fee_refund_log';

    protected $primaryKey = 'refund_fee_id';

    protected $casts = [
        'water_no' => 'string',
        'biz_no' => 'string',
        'refund_no' => 'string',
        'fee_no'=> 'string'
    ];
}
