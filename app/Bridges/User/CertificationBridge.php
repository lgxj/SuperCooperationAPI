<?php
namespace App\Bridges\User;

use App\Bridges\ScBridge;
use App\Services\User\CertificationService;

class CertificationBridge extends ScBridge
{

    public function __construct(CertificationService $service)
    {
        $this->service = $service;
    }
}
