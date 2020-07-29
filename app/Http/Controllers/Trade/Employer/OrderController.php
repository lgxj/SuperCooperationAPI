<?php


namespace App\Http\Controllers\Trade\Employer;
use App\Bridges\User\CodeBridge;
use App\Consts\SmsConst;
use App\Consts\Trade\OrderConst;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Services\Trade\Order\CommentService;
use App\Services\Trade\Order\Employer\AddTaskOrderService;
use App\Services\Trade\Order\Employer\CancelOrderService;
use App\Services\Trade\Order\Employer\ConfirmTaskOrderService;
use App\Services\Trade\Order\Employer\ListOrderService;
use App\Services\Trade\Order\Employer\UpdateTaskOrderService;
use App\Services\User\CodeService;
use App\Utils\AliyunSms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{


    public function __construct()
    {

    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function add(Request $request){
        $data = $request->all();
        $data['user_id'] = $this->getUserId();
        $addTaskOrderService = $this->getAddTaskOrderService();
        $taskOrderEntity = $addTaskOrderService->convertToOrderEntity($data);
        $return = $addTaskOrderService->publish($taskOrderEntity);
        return success($return);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function update(Request $request){
        $data = $request->all();
        $data['user_id'] = $this->getUserId();
        $updateTaskOrderService = $this->getUpdateTaskOrderService();
        $taskOrderEntity = $updateTaskOrderService->convertToOrderEntity($data);
        $return = $updateTaskOrderService->update($taskOrderEntity);
        return success($return);

    }

    /**
     * @param Request $request
     * @return array
     * @throws BusinessException
     */
    public function confirm(Request $request){
        $orderNo = $request->get('order_no');
        $helpUserId = $request->get('helper_user_id');
        $confirmOrderService = $this->getConfirmOrderService();
        $return = $confirmOrderService->confirmHelper($orderNo,$this->getUserId(),$helpUserId);
        return success($return);
    }

    /**
     * @param Request $request
     * @return bool
     * @throws BusinessException
     */
    public function complete(Request $request){
        $orderNo = $request->get('order_no');
        $confirmOrderService = $this->getConfirmOrderService();
        $return = $confirmOrderService->confirmComplete($orderNo,$this->getUserId());
        return success($return);
    }

    /**
     * @param Request $request
     * @return array
     * @throws BusinessException
     */
    public function cancel(Request $request){
        $orderNo = $request->get('order_no');
        $cancelType = $request->get('cancel_type');
        $reason = $request->get('reason','');
        $attachmentList = $request->get('attachment_list',[]);
        $cancelOrderService = $this->getCancelOrderService();
        $return =  $cancelOrderService->cancel($orderNo,$this->getUserId(),$cancelType,$reason,$attachmentList);
        return success($return);
    }

    /**
     * @param Request $request
     * @return array
     * @throws BusinessException
     */
    public function refuseDelivery(Request $request){
        $orderNo = $request->get('order_no');
        $refuseType = $request->get('refuse_type',OrderConst::REFUSE_TYPE_EMPLOYER_LEAVE);
        $refuseReason =  $request->get('refuse_reason','');
        $confirmOrderService = $this->getConfirmOrderService();
        $return = $confirmOrderService->refuseDelivery($orderNo,$this->getUserId(),$refuseType,$refuseReason);
        return success($return);
    }

    public function list(Request $request){
        $status = $request->get('status',0);
        $page = $request->get('page',1);
        $list = $this->getListService()->listByUser($this->getUserId(),$status,$page);
        return success($list);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function comment(Request $request){
        $commentService = $this->getCommentService();
        $orderNo = $request->get('order_no');
        $content = $request->get('content');
        $star = $request->get('star',5);
        $labels = $request->get('labels',[]);
        $attachments = $request->get('attachments',[]);
        $mid = $commentService->commentReceiver($orderNo,$this->getUserId(),$content,$star,$labels,$attachments);
        return success(['message_id'=>$mid]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function checkCancelCompensate(Request $request){
        $orderNo = $request->get('order_no');
        $list = $this->getCancelOrderService()->checkEmployerCancelMoney($orderNo,$this->getUserId());
        return success($list);
    }

    public function agreeDefer(Request $request){
        $orderNo = $request->get('order_no');
        $status = $request->get('status');
        $this->getConfirmOrderService()->confirmDefer($orderNo,$this->getUserId(),$status);
        return success([]);
    }

    protected function getAddTaskOrderService(){
        return new AddTaskOrderService();
    }

    protected function getUpdateTaskOrderService(){
        return new UpdateTaskOrderService();
    }

    protected function getConfirmOrderService(){
        return new ConfirmTaskOrderService();
    }

    protected function getCancelOrderService(){
        return new CancelOrderService();
    }

    protected function getListService(){
        return new ListOrderService();
    }

    protected function getCommentService(){
        return new CommentService();
    }
}
