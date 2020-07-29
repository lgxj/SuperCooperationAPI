<?php
namespace App\Bridges\User;

use App\Bridges\ScBridge;
use App\Services\User\UserPushService;

class UserPushBridge extends ScBridge
{

    /**
     * @var UserPushService
     */
    protected $service;

    public function __construct(UserPushService $service)
    {
        $this->service = $service;
    }
}
