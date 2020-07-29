<?php


namespace App\Bridges\Trade;


use App\Bridges\ScBridge;
use App\Services\Trade\Pay\PayTaskOrderService;

class PayTaskOrderBridge extends ScBridge
{
    public function __construct(PayTaskOrderService $payTaskOrderService)
    {
        $this->service = $payTaskOrderService;
    }
}
