<?php
namespace App\Bridges\Message;

use App\Bridges\ScBridge;
use App\Services\Message\IMService;

class IMBridge extends ScBridge
{
    /**
     * @var IMService
     */
    protected $service;

    public function __construct(IMService $service)
    {
        $this->service = $service;
    }
}
