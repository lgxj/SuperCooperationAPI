<?php
namespace App\Admin\Controllers\Permission;

use App\Admin\Controllers\ScController;
use App\Consts\GlobalConst;
use App\Services\Permission\ApiService;
use Illuminate\Http\Request;

class ApiController extends ScController
{
    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
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
        $columns = ['api_id', 'group_id', 'name', 'path', 'method', 'remark', 'status', 'is_dev', 'need_power', 'sort', 'system_id'];
        $res = $this->apiService->getList($filter, $columns, $pageSize);
        $result = formatPaginate($res);
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
        $path = $request->post('path');
        $method = $request->post('method');
        $status = $request->post('status');
        $is_dev = $request->post('is_dev');
        $need_power = $request->post('need_power');
        $group_id = $request->post('group_id');
        $sort = $request->post('sort');
        $remark = $request->post('remark') ?: '';

        $res = $this->apiService->add($group_id, $name, $path, $method, $status, $is_dev, $need_power, $sort, $remark, $systemId);

        // 记录日志
        $this->getAdminLogService()->create('api-add', '添加API', '添加API：' . $name);

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
        $id = $request->post('api_id');
        $data = $request->only(['group_id', 'name', 'path', 'method', 'status', 'is_dev', 'need_power', 'sort', 'remark', 'system_id']);
        $data['remark'] = $data['remark'] ?: '';
        $this->apiService->edit($id, $data);

        $this->getAdminLogService()->create('api-edit', '修改API', '修改值：' . json_encode_clean($data));

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
        $id = $request->post('api_id');
        $this->apiService->del($id);

        // 记录日志
        $this->getAdminLogService()->create('api-del', '删除API', '删除API【' . $id . '】');

        return success();
    }
}
