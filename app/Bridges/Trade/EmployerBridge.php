<?php


namespace App\Bridges\Trade;


use App\Bridges\ScBridge;
use App\Services\Trade\Order\Employer\EmployerService;

class EmployerBridge extends ScBridge
{
    public function __construct(EmployerService $employerService)
    {
        $this->service = $employerService;
    }
}
