<?php


namespace App\Bridges\Permission;

use App\Services\Permission\AccessService;

class AccessBridge extends BasePermissionBridge
{
    /**
     * @var AccessService
     */
    protected $service;

    public function __construct(AccessService $service)
    {
        $this->service = $service;
    }
}
