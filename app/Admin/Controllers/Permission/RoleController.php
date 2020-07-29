<?php
namespace App\Admin\Controllers\Permission;

use App\Admin\Controllers\ScController;
use App\Consts\GlobalConst;
use App\Consts\PermissionConst;
use App\Services\Permission\RoleService;
use Illuminate\Http\Request;

class RoleController extends ScController
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * 列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getList(Request $request)
    {
        $filter = json_decode($request->post('filter'), true);
        $pageSize = $request->post('limit', GlobalConst::PAGE_SIZE);
        $columns = ['role_id', 'name', 'remark'];

        $systemId = $request->input('system_id', 1);
        $subId = 0;

        $res = $this->roleService->getList($filter, $columns, $pageSize, $systemId, $subId);
        $result = formatPaginate($res);
        return success($result);
    }

    /**
     * 字典
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDic(Request $request)
    {
        $systemId = $request->input('system_id', 1);
        $result = $this->roleService->getDic($systemId);
        return success($result);
    }

    /**
     * 添加
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function add(Request $request)
    {
        $name = $request->post('name');
        $remark = $request->post('remark');
        $resourceIds = $request->post('resourceIds');
        $systemId = $request->post('system_id', 1);

        $subId = 0;

        $res = $this->roleService->add($name, $remark, $resourceIds, $systemId, $subId);

        $this->getAdminLogService()->create('permission-role-add', '添加角色', json_encode_clean([
            'name' => $name,
            'remark' => $remark,
            'resourceIds' => $resourceIds,
            'systemId' => $systemId
        ]));

        return success($res);
    }

    /**
     * 编辑
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function edit(Request $request)
    {
        $id = $request->post('role_id');
        $data = $request->only(['name', 'remark']);

        $systemId = PermissionConst::SYSTEM_MANAGE;
        $subId = 0;

        $this->roleService->edit($id,$systemId,$subId,$data);

        $this->getAdminLogService()->create('permission-role-edit', '编辑角色', json_encode_clean($data));

        return success();
    }

    /**
     * 修改权限
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function editResource(Request $request)
    {
        $id = $request->post('role_id');
        $resourceIds = $request->post('resourceIds', []);
        $this->roleService->editResource($id,PermissionConst::SYSTEM_MANAGE,0,$resourceIds);

        $this->getAdminLogService()->create('permission-role-edit-resource', '编辑角色权限', json_encode_clean([
            'id' => $id,
            'resourceIds' => $resourceIds
        ]));

        return success();
    }

    /**
     * 删除
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function del(Request $request)
    {
        $id = $request->post('role_id');
        $this->roleService->del($id,PermissionConst::SYSTEM_MANAGE,0);

        $this->getAdminLogService()->create('permission-role-del', '删除角色', 'ID: ' . $id);

        return success();
    }
}
