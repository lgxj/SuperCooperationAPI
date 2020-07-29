<?php


namespace App\Admin\Controllers;


use App\Bridges\Permission\AdminLogBridge;
use App\Services\Permission\AdminLogService;
use Illuminate\Routing\Controller;

class ScController extends Controller
{
    public function getPermissionSystemId()
    {
        return 1;
    }

    public function getPermissionSubId()
    {
        return 0;
    }

    public function getUserId()
    {
        return request('userLogin.user_id');
    }

    /**
     * @return AdminLogService
     */
    protected function getAdminLogService()
    {
        return new AdminLogBridge(new AdminLogService());
    }
}
