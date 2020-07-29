<?php
namespace App\Bridges\Message;

use App\Bridges\ScBridge;
use App\Services\Message\MessageService;

class MessageBridge extends ScBridge
{
    /**
     * @var MessageService
     */
    protected $service;

    public function __construct(MessageService $service)
    {
        $this->service = $service;
    }
}
