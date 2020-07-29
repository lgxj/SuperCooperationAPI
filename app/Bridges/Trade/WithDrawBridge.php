<?php


namespace App\Bridges\Trade;


use App\Bridges\ScBridge;
use App\Services\Trade\Fund\WithDrawService;

class WithDrawBridge extends ScBridge
{
    public function __construct(WithDrawService $withDrawService)
    {
        $this->service = $withDrawService;
    }
}
