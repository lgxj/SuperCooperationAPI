<?php


namespace App\Bridges\Trade;


use App\Bridges\ScBridge;
use App\Services\Trade\Order\Employer\DetailTaskOrderService;

class DetailTaskOrderBridge extends ScBridge
{
    public function __construct(DetailTaskOrderService $detailTaskOrderService)
    {
        $this->service = $detailTaskOrderService;
    }
}
