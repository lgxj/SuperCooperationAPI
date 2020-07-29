<?php


namespace App\Bridges\User;


use App\Bridges\ScBridge;
use App\Services\User\UserPositionService;

class UserPositionBridge extends ScBridge
{
    public function __construct(UserPositionService $userPositionService)
    {
        $this->service = $userPositionService;
    }
}
