<?php


namespace App\Bridges\Trade;


use App\Bridges\ScBridge;
use App\Services\Trade\Refund\RefundService;

class RefundBridge extends ScBridge
{
    public function __construct(RefundService $refundService)
    {
        $this->service = $refundService;
    }
}
