<?php

namespace App\Http\Middleware;

use App\Consts\ErrorConst;
use App\Consts\PermissionConst;
use App\Services\Permission\AdminService;
use App\Utils\NoSql\Redis\RedisException;
use Closure;
use Illuminate\Http\Request;

/**
 * 管理员身份验证中间件
 * @package App\Http\Middleware
 */
class Admin
{
    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * 不需要管理员身份接口
     * @var array
     */
    protected $blackList = [
        'permission' => [
            'admin' => ['login']
        ]
    ];

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     * @throws RedisException
     */
    public function handle($request, Closure $next)
    {
        // 判断是否在黑名单
        list($module, $controller, $action) = parseActionName();
        $module = explode('.', $module);
        $module = strtolower(end($module));
        $module = $this->blackList[$module] ?? [];
        $controller = strtolower($controller);
        $controller = $module[$controller] ?? [];
        if (in_array($action, $controller)) {
            return $next($request);
        }

        $token = $request->header('SC-ACCESS-TOKEN');
        if (!$token) {
            return out(ErrorConst::NO_TOKEN, '请先登录', false);
        }

        if (!\Cache::has($token)) {
            return out(ErrorConst::TOKEN_ERROR, '账号已在其它地方登录', false);
        }

        $admin = \Cache::get($token);
        $admin_id = $admin['user_id'] ?? 0;
        if (!$admin_id) {
            return out(ErrorConst::LOGIN_CACHE_ERROR, '登录信息错误', false);
        }

        $subId = $request->header('SC-SUB-ID', null);
        $admin = $this->adminService->getInfo($admin_id, PermissionConst::SYSTEM_MANAGE, $subId);
        if (!$admin) {
            return out(ErrorConst::LOGIN_USER_NOT_FOUND, '账号不存在或已删除', false);
        }

        $admin['token'] = $token;
        request()->offsetSet('admin', $admin);

        return $next($request);
    }
}
