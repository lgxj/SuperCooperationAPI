<?php


namespace App\Bridges\Trade\Admin;


use App\Bridges\ScBridge;
use App\Services\Trade\Fee\Admin\FeeManagerService;

class FeeManagerBridge extends ScBridge
{

    public function __construct(FeeManagerService $service)
    {
        $this->service = $service;
    }
}
