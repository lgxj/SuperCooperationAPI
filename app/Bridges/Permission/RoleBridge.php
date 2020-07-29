<?php


namespace App\Bridges\Permission;

use App\Services\Permission\RoleService;

class RoleBridge extends BasePermissionBridge
{
    /**
     * @var RoleService
     */
    protected $service;

    public function __construct(RoleService $service)
    {
        $this->service = $service;
    }

}
