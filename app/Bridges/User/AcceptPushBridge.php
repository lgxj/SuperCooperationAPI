<?php


namespace App\Bridges\User;


use App\Bridges\ScBridge;
use App\Services\User\AcceptPushService;

class AcceptPushBridge extends  ScBridge
{
    public function __construct(AcceptPushService $acceptPushService)
    {
        $this->service = $acceptPushService;
    }
}
