<?php


namespace App\Models\Trade\Pay;


use App\Models\Trade\BaseTrade;

class PayMessage extends BaseTrade
{
    protected $table = 'pay_msg';

    protected $primaryKey = 'pay_message_id';

    protected $casts = [
        'pay_no' => 'string'
    ];
}
