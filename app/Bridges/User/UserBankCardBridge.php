<?php


namespace App\Bridges\User;


use App\Bridges\ScBridge;
use App\Services\User\BankCardService;

class UserBankCardBridge extends ScBridge
{
    public function __construct(BankCardService $bankCardService)
    {
        $this->service = $bankCardService;
    }
}
