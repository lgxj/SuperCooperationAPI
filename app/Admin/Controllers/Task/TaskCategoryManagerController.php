<?php
/**
 * 任务分类相关接口
 */
namespace App\Admin\Controllers\Task;

use App\Admin\Controllers\ScController;
use App\Bridges\Permission\AdminLogBridge;
use App\Services\Permission\AdminLogService;
use App\Bridges\Trade\Category\TaskCategoryManagerBridge;
use App\Services\Trade\Category\TaskCategoryManagerService;
use Illuminate\Http\Request;

class TaskCategoryManagerController extends ScController
{
    /**
     * @var TaskCategoryManagerService
     */
    protected $managerService;
    /**
     * @var AdminLogService
     */
    protected $adminLogService;

    public function __construct(TaskCategoryManagerBridge $service, AdminLogBridge $adminLogBridge)
    {
        $this->managerService = $service;
        $this->adminLogService = $adminLogBridge;
    }

    /**
     * 列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getList(Request $request)
    {
        $pageSize = $request->input('limit', 10);
        $result = $this->managerService->getList($pageSize);
        return success(formatPaginate($result));
    }

    /**
     * 添加分类
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function add(Request $request)
    {
        $categoryName = $request->input('category_name');
        $sort = $request->input('sort');
        $result = $this->managerService->add($categoryName, $sort);
        if ($result) {
            // 记录日志
            $this->adminLogService->create('task-category-add', '添加任务分类', '分类名: ' . $categoryName);
            return success($result);
        } else {
            return out(1, '添加失败');
        }
    }

    /**
     * 编辑分类
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function edit(Request $request)
    {
        $categoryId = $request->input('category_id');
        $categoryName = $request->input('category_name');
        $sort = $request->input('sort');
        $result = $this->managerService->edit($categoryId, $categoryName, $sort);
        if ($result) {
            // 记录日志
            $this->adminLogService->create('task-category-edit', '修改任务分类', json_encode($request->input(), 320));
            return success($result);
        } else {
            return out(1, '修改失败');
        }
    }

    /**
     * 删除分类
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function del(Request $request)
    {
        $categoryId = $request->input('category_id');
        $result = $this->managerService->del($categoryId);
        if ($result) {
            // 记录日志
            $this->adminLogService->create('task-category-del', '删除任务分类', '分类ID：' . $categoryId);
            return success($result);
        } else {
            return out(1, '删除失败');
        }
    }

    /**
     * key-value
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDic()
    {
        $result = $this->managerService->getDic();
        return success($result);
    }
}
