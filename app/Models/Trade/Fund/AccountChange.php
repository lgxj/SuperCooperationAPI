<?php


namespace App\Models\Trade\Fund;


use App\Models\Trade\BaseTrade;

class AccountChange extends BaseTrade
{
    protected $table = 'fund_account_change';

    protected $primaryKey = 'account_change_id';
}
