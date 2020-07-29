<?php
namespace App\Admin\Controllers\User;

use App\Admin\Controllers\ScController;
use App\Bridges\User\FeedbackBridge;
use App\Consts\FeedbackConst;
use App\Services\User\feedbackService;
use Illuminate\Http\Request;

class FeedbackController extends ScController
{

    /**
     * @var feedbackService
     */
    protected $service;

    public function __construct(FeedbackBridge $service)
    {
        $this->service = $service;
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
     * 反馈类型
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTypes()
    {
        return success(FeedbackConst::getTypeList());
    }

    /**
     * 删除
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function del(Request $request)
    {
        $id = $request->input('id');
        $this->service->del($id);
        return success();
    }

}
