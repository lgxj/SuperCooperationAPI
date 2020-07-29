<?php


namespace App\Models\Trade\Fund;


use App\Models\Trade\BaseTrade;

class WithdrawApply extends BaseTrade
{
    protected $table = 'withdraw_apply';

    protected $primaryKey = 'withdraw_id';

    protected $casts = [
        'withdraw_no' => 'string'
    ];
}
