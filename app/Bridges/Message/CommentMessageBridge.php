<?php
namespace App\Bridges\Message;

use App\Bridges\ScBridge;
use App\Services\Message\CommentMessageService;

class CommentMessageBridge extends ScBridge
{
    /**
     * @var CommentMessageService
     */
    protected $service;

    public function __construct(CommentMessageService $service)
    {
        $this->service = $service;
    }
}
