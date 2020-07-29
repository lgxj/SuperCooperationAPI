<?php
namespace App\Bridges\Permission;

use App\Services\Permission\AdminLogService;

class AdminLogBridge extends BasePermissionBridge
{

    /**
     * @var AdminLogService
     */
    protected $service;

    public function __construct(AdminLogService $service)
    {
        $this->service = $service;
    }

}
