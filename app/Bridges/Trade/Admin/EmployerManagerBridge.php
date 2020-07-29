<?php


namespace App\Bridges\Trade\Admin;


use App\Bridges\ScBridge;
use App\Services\Trade\Order\Admin\EmployerManagerService;

class EmployerManagerBridge extends ScBridge
{

    public function __construct(EmployerManagerService $employerManagerService)
    {
        $this->service = $employerManagerService;
    }
}
