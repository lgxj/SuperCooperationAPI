<?php


namespace App\Bridges\Pool;


use App\Bridges\ScBridge;
use App\Services\Pool\AddressService;

class AddressBridge extends ScBridge
{
    public function __construct(AddressService $addressService)
    {
        $this->service = $addressService;
    }
}
