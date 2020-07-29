<?php

namespace App\Http\Middleware;

use App\Consts\ErrorConst;
use App\Consts\PermissionConst;
use App\Services\Permission\AccessService;
use App\Services\Permission\AdminService;
use App\Services\Permission\ApiService;
use Closure;
use Illuminate\Http\Request;

/**
 * 后台接口访问权限判断
 * @package App\Http\Middleware
 */
class Permission
{
    protected $apiService;
    protected $adminService;

    public function __construct(ApiService $apiService, AdminService $adminService)
    {
        $this->apiService = $apiService;
        $this->adminService = $adminService;
    }

    /**
     * Handle an incoming request.
     * @param $request
     * @param Closure $next
     * @return \Illuminate\Http\JsonResponse|mixed
     * @throws \App\Utils\NoSql\Redis\RedisException
     */
    public function handle(Request $request, Closure $next)
    {
        $path = $request->path();
        $method = $request->method();
        $adminId = $request->input('admin.admin_id');
        if (AccessService::visit($adminId,PermissionConst::SYSTEM_MANAGE,0,$path,$method)) {
            return out(ErrorConst::LOGIN_USER_NO_POWER, '您没有访问此功能权限，请联系上级或管理员', false);
        }

        return $next($request);
    }
}
