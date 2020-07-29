<?php


namespace App\Bridges\User;


use App\Bridges\ScBridge;
use App\Services\User\UserService;

class UserBridge extends ScBridge
{
    public function __construct(UserService $userService)
    {
        $this->service = $userService;
    }
}
