<?php


namespace App\Http\Controllers\Trade\Helper;


use App\Bridges\User\CertificationBridge;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Services\Trade\Fee\FeeTaskService;
use App\Services\Trade\Order\CommentService;
use App\Services\Trade\Order\Helper\HelperService;
use App\Services\Trade\Order\Helper\ListOrderService;
use App\Services\Trade\Order\Helper\ReceiveTaskOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $receiverOrderService = null;

    public function __construct(ReceiveTaskOrderService $receiverOrderService)
    {
        $this->receiverOrderService = $receiverOrderService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function receive(Request $request)
    {
        $orderNo = $request->get('order_no');
        $memo = $request->get('memo','');
        $quotedPrice = $request->get('quoted_price',0);
        $result = $this->receiverOrderService->receive($orderNo,$this->getUserId(),$memo,$quotedPrice);
        return success($result);
    }

    /**
     * 判断当前帮手与在指定订单是否已扫脸
     * @param Request $request
     * @param CertificationBridge $bridge
     * @return JsonResponse
     */
    public function receiveCheckFace(Request $request, CertificationBridge $bridge)
    {
        $orderNo = $request->get('order_no');
        $result = $bridge->isFaceAuth($this->getUserId(), $orderNo);
        return success(['isAuth' => !!$result]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function delivery(Request $request){
        $orderNo = $request->get('order_no');
        $userId = $this->getUserId();
        $result = $this->receiverOrderService->delivery($orderNo,$userId);
        return success($result);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function cancel(Request $request){
        $orderNo = $request->get('order_no');
        $cancelType = $request->get('cancel_type');
        $reason = $request->get('reason','');
        $attachmentList = $request->get('attachment_list',[]);
        $userId = $this->getUserId();
        $result = $this->receiverOrderService->cancel($orderNo,$userId,$cancelType,$reason,$attachmentList);
        return success($result);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function position(Request $request){
        $helperService = $this->getHelperService();
        $address['province'] = $request->get('province');
        $address['city'] = $request->get('city');
        $address['region'] = $request->get('region');
        $address['street'] = $request->get('street','');
        $address['address_detail'] = $request->get('address_detail');
        $address['lng'] = $request->get('lng','');
        $address['lat'] = $request->get('lat','');

        $return = $helperService->saveYuTuAddress($address,$this->getUserId());
        return success($return);
    }

    public function list(Request $request){
        $lng = $request->get('lng','');
        $lat = $request->get('lat','');
        $status = $request->get('status',0);
        $page = $request->get('page',1);
        $list = $this->getListService()->listByUser($this->getUserId(),$lng,$lat,$status,$page);
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
        $mid = $commentService->commentEmployer($orderNo,$this->getUserId(),$content,$star,$labels,$attachments);
        return success(['message_id'=>$mid]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function cancelQuoted(Request $request){
        $orderNo = $request->get('order_no');
        $this->receiverOrderService->cancelQuoted($orderNo,$this->getUserId());
        return success([]);
    }


    public function getFee(Request $request){
        $orderNo = $request->get('order_no');
        $list = $this->receiverOrderService->getFee($orderNo);
        return success($list);
    }

    public function getPriceFee(Request $request){
        $price = $request->get('price',0);
        $list = (new FeeTaskService())->computePriceFee($price);
        return success($list);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function checkOverTimeCompensate(Request $request){
        $orderNo = $request->get('order_no');
        $list = $this->receiverOrderService->checkHelperOvertimeMoney($orderNo,$this->getUserId());
        return success($list);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function checkCancelCompensate(Request $request){
        $orderNo = $request->get('order_no');
        $list = $this->receiverOrderService->checkHelperCancelMoney($orderNo,$this->getUserId());
        return success($list);
    }


    public function defer(Request $request){
        $orderNo = $request->get('order_no');
        $deferMinute = $request->get('defer_minute',0);
        $reason = $request->get('reason','');
        $this->receiverOrderService->defer($orderNo,$this->getUserId(),$deferMinute,$reason);
        return success([]);

    }
    protected function getHelperService(){
        return new HelperService();
    }

    protected function getListService(){
        return new ListOrderService();
    }

    protected function getCommentService(){
        return new CommentService();
    }
}
