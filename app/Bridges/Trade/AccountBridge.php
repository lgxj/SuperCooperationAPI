<?php


namespace App\Bridges\Trade;


use App\Bridges\ScBridge;
use App\Services\Trade\Fund\AccountService;

class AccountBridge extends ScBridge
{
    public function __construct(AccountService $accountService)
    {
        $this->service = $accountService;
    }
}
