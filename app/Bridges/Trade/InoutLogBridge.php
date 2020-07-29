<?php


namespace App\Bridges\Trade;


use App\Bridges\ScBridge;
use App\Services\Trade\Fund\InoutLogService;

class InoutLogBridge extends ScBridge
{
    public function __construct(InoutLogService $inoutLogService)
    {
        $this->service = $inoutLogService;
    }
}
