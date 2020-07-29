<?php


namespace App\Bridges\User;


use App\Bridges\ScBridge;
use App\Services\User\BlackService;

class BlackBridge extends ScBridge
{
    public function __construct(BlackService $blackService)
    {
        $this->service = $blackService;
    }
}
