<?php
namespace App\Bridges\Permission;

use App\Services\Permission\AdminService;

class AdminBridge extends BasePermissionBridge
{

    /**
     * @var AdminService
     */
    protected $service;

    public function __construct(AdminService $service)
    {
        $this->service = $service;
    }

}
