<?php


namespace App\Models\Trade\Fund;


use App\Models\Trade\BaseTrade;

class Account extends BaseTrade
{
    protected $table = 'fund_account';

    protected $primaryKey = 'fund_account_id';
}
