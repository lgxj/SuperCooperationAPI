<?php


namespace App\Http\Controllers\Message;


use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Services\Message\OrderMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $noticeService;

    public function __construct(OrderMessageService $noticeMessageService)
    {
        $this->noticeService = $noticeMessageService;
    }

    public function getReceiveNotices(Request $request){
        $page = $request->get('page',1);
        $list = $this->noticeService->getReceiveNoticesByUserId($this->getUserId(),null,$page);
        return success($list);
    }

    /**
     * 消息详情
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function getDetail(Request $request)
    {
        $id = $request->input('id');
        $result = $this->noticeService->getDetail($id, $this->getUserId());
        return success($result);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function deleteMsgByRid(Request $request){
        $id = $request->get('id');
        $result = $this->noticeService->deleteMsgByRid($this->getUserId(),$id,true);
        return success(['status'=>$result]);
    }
}
