<?php
/**
 * APP更新信息管理
 */
namespace App\Admin\Controllers\Pool;

use App\Admin\Controllers\ScController;
use App\Bridges\Permission\AdminLogBridge;
use App\Services\Common\UpgradeService;
use App\Services\Permission\AdminLogService;
use App\Services\Pool\ArticleCategoryService;
use App\Consts\GlobalConst;
use Illuminate\Http\Request;

class UpgradeController extends ScController
{
    /**
     * @var UpgradeService
     */
    protected $service;
    /**
     * @var AdminLogService
     */
    protected $adminLogService;


    public function __construct(UpgradeService $service, AdminLogBridge $adminLogBridge)
    {
        $this->service = $service;
        $this->adminLogService = $adminLogBridge;
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
        $res = $this->service->getList($filter, $pageSize);
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
        $data = $request->only(['app_type', 'description', 'download_url', 'is_force', 'is_gray', 'is_hot', 'is_tip', 'version', 'version_name']);
        $res = $this->service->add($data);
        // 记录日志
        $this->adminLogService->create('upgrade-add', '添加APP更新', $data);
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
        $data = $request->only(['app_type', 'description', 'download_url', 'is_force', 'is_gray', 'is_hot', 'is_tip', 'version', 'version_name']);
        $id = $request->post('app_id');
        $this->service->edit($id, $data);
        // 记录日志
        $this->adminLogService->create('upgrade-update', '更新APP更新', $data);
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
        $id = $request->post('app_id');
        $this->service->del($id);
        // 记录日志
        $this->adminLogService->create('upgrade-del', '删除APP更新', 'id: ' . $id);
        return success();
    }
}
