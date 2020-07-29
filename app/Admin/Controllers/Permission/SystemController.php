<?php
namespace App\Admin\Controllers\Permission;

use App\Admin\Controllers\ScController;
use App\Bridges\Permission\AdminLogBridge;
use App\Consts\GlobalConst;
use App\Services\Permission\AdminLogService;
use App\Services\Permission\SystemService;
use Illuminate\Http\Request;

class SystemController extends ScController
{
    protected $systemService;

    /**
     * @var AdminLogService
     */
    protected $adminLogService;

    public function __construct(SystemService $systemService, AdminLogBridge $adminLogBridge)
    {
        $this->systemService = $systemService;
        $this->adminLogService = $adminLogBridge;
    }

    /**
     * 列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getList(Request $request)
    {
        $pageSize = $request->post('limit', GlobalConst::PAGE_SIZE);
        $columns = ['system_id', 'system_name', 'domain', 'desc'];
        $res = $this->systemService->getList($pageSize, $columns);
        $result = formatPaginate($res);
        return success($result);
    }

    /**
     * 获取字典
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDic()
    {
        $result = $this->systemService->getDic();
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
        $name = $request->post('system_name');
        $domain = $request->post('domain', '');
        $desc = $request->post('desc', '');

        $res = $this->systemService->add($name, $domain, $desc);

        $this->adminLogService->create('system-add', '添加系统', '添加系统: ' . $name);
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
        $id = $request->post('system_id', 1);
        $data = $request->only(['system_name', 'domain', 'desc']);
        $this->systemService->edit($id, $data);
        $this->adminLogService->create('system-edit', '编辑系统', json_encode_clean($request->input()));
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
        $id = $request->post('system_id', 1);
        $this->systemService->del($id);
        $this->adminLogService->create('system-del', '删除系统', 'ID: ' . $id);
        return success();
    }
}
