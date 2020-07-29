<?php
namespace App\Bridges\User;

use App\Bridges\ScBridge;
use App\Services\User\CustomerService;

class CustomerBridge extends ScBridge
{
    public function __construct(CustomerService $customerService)
    {
        $this->service = $customerService;
    }

}
