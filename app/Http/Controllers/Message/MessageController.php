<?php
namespace App\Http\Controllers\Message;

use App\Http\Controllers\Controller;
use App\Services\Message\MessageCounterService;
use App\Services\Message\MessageService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    protected $counterService;
    protected $messageService;

    public function __construct(MessageCounterService $service, MessageService $messageService)
    {
        $this->counterService = $service;
        $this->messageService = $messageService;
    }

    /**
     * 未读消息信息
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnReadNum()
    {
        $result = $this->counterService->getUnreadNum($this->getUserId());
        return success($result);
    }

    /**
     * 消息列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getList(Request $request)
    {
        $pageSize = $request->input('pageSize');
        $type = $request->input('type');

        $result = $this->messageService->getListByPage($this->getUserId(), $type,null,0, null,$pageSize);
        return success(formatPaginate($result));
    }

    /**
     * 消息详情
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function getDetail(Request $request)
    {
        $id = $request->input('id');
        $result = $this->messageService->getDetail($id, $this->getUserId());
        return success($result);
    }

}
