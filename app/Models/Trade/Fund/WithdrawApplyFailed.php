<?php


namespace App\Models\Trade\Fund;


use App\Models\Trade\BaseTrade;

class WithdrawApplyFailed extends BaseTrade
{
    protected $table = 'withdraw_apply';

    protected $primaryKey = 'failed_id';
}
