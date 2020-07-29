<?php
namespace App\Admin\Controllers\Permission;

use App\Admin\Controllers\ScController;
use App\Bridges\Permission\AdminLogBridge;
use App\Consts\GlobalConst;
use App\Consts\PermissionConst;
use App\Services\Permission\AdminLogService;
use App\Services\Permission\AdminService;
use Illuminate\Http\Request;

class AdminController extends ScController
{
    protected $adminService;

    /**
     * @var AdminLogService
     */
    protected $adminLogBridge;

    public function __construct(AdminService $adminService, AdminLogBridge $adminLogBridge)
    {
        $this->adminService = $adminService;
        $this->adminLogBridge = $adminLogBridge;
    }

    /**
     * 管理员登录
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function login(Request $request)
    {
        $username = $request->post('username');
        $password = $request->post('password');
        return success($this->adminService->login($username, $password, $this->getPermissionSystemId()));
    }

    /**
     * 管理员信息
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function info(Request $request)
    {
        $id = $request->input('admin.user_id');
        return success($this->adminService->getInfo($id));
    }

    /**
     * 退出登录
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function logout(Request $request)
    {
        $token = $request->input('admin.token');
        $this->adminService->logout($token);
        return success();
    }

    /**
     * 添加管理员
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function add(Request $request)
    {
        $username = $request->post('phone');
        $password = $request->post('password');
        $avatar = $request->post('user_avatar');
        $name = $request->post('user_name');
        $roleIds = $request->post('roleIds');

        $systemId = PermissionConst::SYSTEM_MANAGE;
        $subId = 0;

        $res = $this->adminService->add($username, $password, $avatar, $name, $systemId, $subId,$roleIds);
        return success($res);
    }

    /**
     * 编辑管理员信息
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function edit(Request $request)
    {
        $id = $request->post('user_id');
        $roleIds = $request->post('roleIds', []);
        $data = $request->only(['user_name', 'user_avatar']);

        $systemId = PermissionConst::SYSTEM_MANAGE;
        $subId = 0;

        $this->adminService->edit($id, $data['user_name'],$data['user_avatar'], $systemId, $subId,$roleIds);
        return success();
    }

    /**
     * 管理员列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getList(Request $request)
    {
        $filter = $request->post('filter');
        $pageSize = $request->post('limit', GlobalConst::PAGE_SIZE);
        $page = $request->post('page', 1);

        $systemId = PermissionConst::SYSTEM_MANAGE;
        $subId = 0;

        $res = $this->adminService->getList($filter, $page, $pageSize, $systemId, $subId);
        return success($res);
    }

    /**
     * 重置密码
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function resetPwd(Request $request)
    {
        $id = $request->post('user_id');
        $password = $request->post('password');
        if ($this->adminService->resetPwd($id, $password, $this->getPermissionSystemId(), $this->getPermissionSubId())) {
            return success();
        } else {
            return out(1, '重置密码失败', false);
        }
    }

    /**
     * 修改自己密码
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function resetSelfPwd(Request $request)
    {
        $pwdOld = $request->input('password_old');
        $pwd = $request->input('password');
        $userId = $this->getUserId();
        $this->adminService->resetSelfPwd($pwdOld, $pwd, $userId);
        return success();
    }

    /**
     * 操作日志列表
     * @param Request $request
     * @param AdminLogService $adminLogService
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLogs(Request $request, AdminLogService $adminLogService)
    {
        $pageSize = $request->post('limit', GlobalConst::PAGE_SIZE);
        $filter = json_decode($request->post('filter'), true);

        $systemId = PermissionConst::SYSTEM_MANAGE;
        $subId = 0;

        $res = $adminLogService->getList($filter, ['*'], $pageSize, $systemId, $subId);
        $result = formatPaginate($res);
        return success($result);
    }

    public function del(Request $request)
    {
        $id = $request->input('user_id');
        $this->adminService->delManager($id, PermissionConst::SYSTEM_MANAGE, 0);
        $this->getAdminLogService()->create('admin-delete', '删除管理员', '管理员【' . $id . '】');
        return success();
    }

    public function searchUserByPhone(Request $request)
    {
        $phone = $request->input('phone');
        $res = $this->adminService->getBaseByPhone($phone);
        return success($res);
    }
}
