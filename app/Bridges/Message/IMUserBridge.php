<?php
namespace App\Bridges\Message;

use App\Bridges\ScBridge;
use App\Services\Message\IMUserService;

class IMUserBridge extends ScBridge
{
    public function __construct(IMUserService $service)
    {
        $this->service = $service;
    }
}
