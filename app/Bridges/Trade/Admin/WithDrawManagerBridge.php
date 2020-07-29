<?php


namespace App\Bridges\Trade\Admin;


use App\Bridges\ScBridge;
use App\Services\Trade\Fund\Admin\WithDrawManagerService;

class WithDrawManagerBridge extends ScBridge
{
    public function __construct(WithDrawManagerService $withDrawManagerService)
    {
        $this->service = $withDrawManagerService;
    }
}
