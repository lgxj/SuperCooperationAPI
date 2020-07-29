<?php
namespace App\Bridges\Message;

use App\Bridges\ScBridge;
use App\Services\Message\PushService;

class PushBridge extends ScBridge
{
    /**
     * @var PushService
     */
    protected $service;

    public function __construct(PushService $service)
    {
        $this->service = $service;
    }
}
