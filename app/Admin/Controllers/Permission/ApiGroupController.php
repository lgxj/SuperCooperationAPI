<?php
namespace App\Admin\Controllers\Permission;

use App\Admin\Controllers\ScController;
use App\Consts\GlobalConst;
use App\Services\Permission\ApiGroupService;
use Illuminate\Http\Request;

class ApiGroupController extends ScController
{
    protected $apiGroupService;

    public function __construct(ApiGroupService $apiGroupService)
    {
        $this->apiGroupService = $apiGroupService;
    }

    /**
     * 列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getList(Request $request)
    {
        $systemId = $request->post('system_id', 1);
        $pageSize = $request->post('limit', GlobalConst::PAGE_SIZE);
        $columns = ['api_group_id', 'name', 'sort'];
        $res = $this->apiGroupService->getList($systemId, $columns, $pageSize);
        $result = formatPaginate($res);
        return success($result);
    }

    /**
     * 获取字典
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDic(Request $request)
    {
        $systemId = $request->input('system_id', 1);
        $result = $this->apiGroupService->getDic($systemId);
        return success($result);
    }

    /**
     * 获取树
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTree(Request $request)
    {
        $systemId = $request->input('system_id', 1);
        $result = $this->apiGroupService->getTree($systemId);
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
        $systemId = $request->post('system_id', 1);
        $name = $request->post('name');
        $sort = $request->post('sort');

        $res = $this->apiGroupService->add($systemId, $name, $sort);

        // 记录日志
        $this->getAdminLogService()->create('api-group-add', '添加API分组', json_encode_clean(['systemId' => $systemId, 'name' => $name]));

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
        $id = $request->post('api_group_id');
        $data = $request->only(['name', 'sort']);
        $this->apiGroupService->edit($id, $data);

        // 记录日志
        $this->getAdminLogService()->create('api-group-edit', '修改API分组', '修改值：' . json_encode_clean($data));

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
        $id = $request->post('api_group_id');
        $this->apiGroupService->del($id);

        // 记录日志
        $this->getAdminLogService()->create('api-group-del', '删除API分组', '删除API分组【' . $id . '】');

        return success();
    }
}
