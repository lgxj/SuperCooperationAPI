<?php


namespace App\Bridges\Trade\Admin;


use App\Bridges\ScBridge;
use App\Services\Trade\Order\Admin\HelperManagerService;

class HelperManagerBridge extends ScBridge
{

    public function __construct(HelperManagerService $helperManagerService)
    {
        $this->service = $helperManagerService;
    }
}
