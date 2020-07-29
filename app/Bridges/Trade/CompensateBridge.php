<?php


namespace App\Bridges\Trade;


use App\Bridges\ScBridge;
use App\Services\Trade\Fund\CompensateService;

class CompensateBridge extends  ScBridge
{
    public function __construct(CompensateService $compensateService)
    {
        $this->service = $compensateService;
    }
}
