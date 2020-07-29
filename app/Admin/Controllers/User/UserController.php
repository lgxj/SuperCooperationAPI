<?php

namespace App\Admin\Controllers\User;

use App\Admin\Controllers\ScController;
use App\Bridges\Permission\AdminLogBridge;
use App\Bridges\User\UserBridge;
use App\Bridges\User\UserPositionBridge;
use App\Services\Permission\AdminLogService;
use App\Services\User\UserPositionService;
use App\Services\User\UserService;
use Illuminate\Http\Request;

class UserController extends ScController
{

    /**
     * @var UserService
     */
    protected $service;
    /**
     * @var AdminLogService
     */
    protected $adminLogService;

    public function __construct(UserBridge $service, AdminLogBridge $adminLogBridge)
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
        $filter = $request->input('filter');
        $filter = json_decode($filter, true);
        $limit = $request->input('limit');

        $result = $this->service->getListByPage($filter, $limit);
        return success(formatPaginate($result));
    }

    /**
     * 获取实名认证信息
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function getCertification(Request $request)
    {
        $userId = $request->input('user_id');
        $result = $this->service->getCertification($userId);
        return success($result);
    }

    /**
     * 冻结用户
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function frozen(Request $request)
    {
        $userId = $request->input('user_id');
        $result = $this->service->frozen($userId);
        if ($result) {
            // 记录日志
            $this->adminLogService->create('user-frozen', '冻结用户', 'user_id: ' . $userId);
            return success();
        } else {
            return out(1, '冻结失败');
        }
    }

    /**
     * 解冻用户
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unFrozen(Request $request)
    {
        $userId = $request->input('user_id');
        $result = $this->service->unFrozen($userId);
        if ($result) {
            // 记录日志
            $this->adminLogService->create('user-unFrozen', '解冻用户', 'user_id: ' . $userId);
            return success();
        } else {
            return out(1, '解冻失败');
        }
    }

    public function getDetail(Request $request)
    {
        $userId = $request->input('user_id');
        $result = $this->service->getDetail($userId);
        return success($result);
    }

    public function positionList(Request $request)
    {
        $filter = $request->input('filter');
        $filter = json_decode($filter, true);
        $limit = $request->input('limit');
        /** @var UserPositionService $userPositionService */
        $userPositionService = new UserPositionBridge(new UserPositionService());
        $result = $userPositionService->search($filter, $limit);
        return success(formatPaginate($result));
    }
}
