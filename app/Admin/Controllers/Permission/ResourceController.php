<?php
namespace App\Admin\Controllers\Permission;

use App\Admin\Controllers\ScController;
use App\Consts\GlobalConst;
use App\Services\Permission\ResourceService;
use Illuminate\Http\Request;

class ResourceController extends ScController
{
    protected $resourceService;

    public function __construct(ResourceService $resourceService)
    {
        $this->resourceService = $resourceService;
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
        $columns = ['resource_id', 'fid', 'name', 'code', 'status', 'is_dev', 'remark'];
        $res = $this->resourceService->getList($filter, $columns, $pageSize);
        $result = formatPaginate($res);
        return success($result);
    }

    /**
     * 获取子集
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTree(Request $request)
    {
        $fid = $request->input('fid');
        $children = $request->input('children', true);
        $systemId = $request->input('system_id', 1);

        $res = $this->resourceService->getChildren($fid, $children, $systemId);
        return success($res);
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
        $code = $request->post('code');
        $fid = $request->post('fid', 0);
        $type = $request->post('type', 0);
        $sort = $request->post('sort', 0);
        $status = $request->post('status', 1);
        $is_dev = $request->post('is_dev', 0);
        $remark = $request->post('remark') ?: '';
        $apiIds = $request->post('apiIds', []);
        $systemId = $request->post('system_id', 1);

        $res = $this->resourceService->add($fid, $type, $name, $code, $sort, $status, $is_dev, $remark, $apiIds, $systemId);

        // 记录日志
        $this->getAdminLogService()->create('resource-add', '添加资源', '资源名：' . $name);

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
        $id = $request->post('resource_id');
        $apiIds = $request->post('apiIds');
        $systemId = $request->post('system_id', 1);
        $data = $request->only(['fid', 'type', 'name', 'code','sort', 'status', 'is_dev',  'remark']);
        $data['remark'] = $data['remark'] ?: '';
        $this->resourceService->edit($id, $data, $apiIds, $systemId);

        // 记录日志
        $this->getAdminLogService()->create('resource-edit', '修改资源', '修改值：' . json_encode_clean($data));

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
        $id = $request->post('resource_id');
        $this->resourceService->del($id);

        // 记录日志
        $this->getAdminLogService()->create('resource-del', '删除资源', '删除资源【' . $id . '】');

        return success();
    }
}
