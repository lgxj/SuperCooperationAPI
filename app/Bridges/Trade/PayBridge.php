<?php


namespace App\Bridges\Trade;


use App\Bridges\ScBridge;
use App\Services\Trade\Pay\PayService;

class PayBridge extends ScBridge
{
    public function __construct(PayService $payService)
    {
        $this->service = $payService;
    }
}
