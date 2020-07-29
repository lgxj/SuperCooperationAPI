<?php


namespace App\Services\Trade\Order\Helper;


use App\Bridges\User\BlackBridge;
use App\Bridges\User\CertificationBridge;
use App\Consts\DBConnection;
use App\Consts\ErrorCode\ReceiveErrorCode;
use App\Consts\ErrorCode\TaskOrderErrorCode;
use App\Consts\Trade\OrderConst;
use App\Consts\Trade\PayConst;
use App\Consts\UserConst;
use App\Events\TaskOrder\TaskDeliveryEvent;
use App\Events\TaskOrder\TaskHelperCancelEvent;
use App\Events\TaskOrder\TaskReceiveEvent;
use App\Events\TaskOrder\TaskReverseStartEvent;
use App\Exceptions\BusinessException;
use App\Models\Trade\Entity\TaskOrderEntity;
use App\Models\Trade\Order\Defer;
use App\Services\Trade\Order\BaseTaskOrderService;
use App\Services\User\BlackService;
use App\Services\User\CertificationService;
use App\Utils\UniqueNo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * 帮手接单管理
 *
 * Class ReceiveTaskOrderService
 * @package App\Services\Trade\Order\Helper
 */
class ReceiveTaskOrderService extends  BaseTaskOrderService
{


    /**
     * 接单
     *
     * @param $orderNo
     * @param $userId
     * @param string $memo
     * @param int $quotedPrice
     * @return array
     * @throws BusinessException
     */
    public function receive($orderNo,$userId,$memo='',$quotedPrice = 0){
        if(empty($orderNo)){
            throw new BusinessException('订单号错误',TaskOrderErrorCode::SAVE_PARAM_CHECK_NO);
        }
        if($userId <= 0){
            throw new BusinessException('用户信息错误',TaskOrderErrorCode::SAVE_PARAM_CHECK_USER);
        }
        $userService = $this->getUserService();
        $taskOrderModel = $this->getTaskOrderModel();
        $receiverModel = $this->getReceiveModel();
        $users = $userService->users([$userId]);
        $user = $users[$userId] ?? [];
        if(!$user){
            throw new BusinessException('用户不存在',TaskOrderErrorCode::SAVE_PARAM_CHECK_USER);
        }
        if(!$user['is_certification']){
            throw new BusinessException('您还没有实名认证，必须实名认证后接单',ReceiveErrorCode::RECEIVE_CERTIFICATION_NOT);
        }

        if(!$user['user_status']){
            throw new BusinessException('您在平台有非法操作，暂时不能接单，请联系客服',ReceiveErrorCode::RECEIVE_FORBIDDEN);
        }

        $compensate = $receiverModel->getCompensateOrder($userId,OrderConst::CANCEL_COMPENSATE_STATUS_HAS);
        if($compensate){
            $compensatePriceList = $this->getDetailService()->getLatestChangePayPrice($compensate['order_no'], OrderConst::PRICE_CHANGE_HELPER_CANCEL, true);
            $compensatePrice = array_sum(array_values($compensatePriceList));
            throw new BusinessException($compensate['order_no'].':'.$compensatePrice,ReceiveErrorCode::RECEIVE_COMPENSATE);
        }
        $taskOrder = $taskOrderModel->getByOrderNo($orderNo);
        if(!$taskOrder){
            throw new BusinessException('任务不存在',TaskOrderErrorCode::SAVE_PARAM_CHECK_TASK_NOT_EXIST);
        }
        if($this->getBlackBridge()->get($taskOrder['user_id'],$userId)){
            throw new BusinessException('您已被雇主拉黑了，请联系雇主',ReceiveErrorCode::RECEIVE_BLACKED);
        }
        if($user['helper_level'] < $taskOrder['helper_level']){
            throw new BusinessException('您不符合雇主要求的星级，不能抢单/竞价',ReceiveErrorCode::RECEIVE_LEVEL_NOT_ENOUGH);
        }
        if($userId == $taskOrder['user_id']){
            throw new BusinessException('您不能接自己发的任务',ReceiveErrorCode::RECEIVE_NOT_SELF);
        }
        $receiver = $receiverModel->getOrderHelper($orderNo,$userId);
        if(in_array($taskOrder['order_state'],[OrderConst::EMPLOYER_STATE_UN_START])){
            throw new BusinessException('雇主还未付款，不能抢单/竞价',TaskOrderErrorCode::CONFIRM_UN_PAY);
        }
        if(!in_array($taskOrder['order_state'],[OrderConst::EMPLOYER_STATE_UN_RECEIVE,OrderConst::EMPLOYER_STATE_UN_CONFIRM])){
            throw new BusinessException('任务已被其他人抢走了，去任务大厅在看看吧',ReceiveErrorCode::RECEIVE_OTHER_RECEIVED);
        }
        if($receiverModel->totalDoingTaskOrder($userId) > 0){
            throw new BusinessException('您还有没交付的任务，请交付后再接单',TaskOrderErrorCode::STATE_NO_DELIVERED);
        }
        if($receiver && $receiver['receive_state'] == OrderConst::HELPER_STATE_CANCEL) {
            throw new BusinessException('您已经取消过互任务了，不能再次接单',TaskOrderErrorCode::STATE_CANCELED);
        }
        $enableServices = $this->getDetailService()->getEnableServiceByOrderNo($orderNo);
        //开启人脸接单
        if(isset($enableServices[OrderConst::SERVICE_PRICE_TYPE_FACE]) && !$this->getCertificationBridge()->isFaceAuth($userId,$orderNo,UserConst::FACE_AUTH_TYPE_RECEIVE)){
            throw new BusinessException('雇主已开启人脸接单了，请人脸识别后接单',ReceiveErrorCode::RECEIVE_FACE_CERTIFY);
        }
        if($taskOrder['order_type'] == OrderConst::TYPE_COMPETITION){
            if($quotedPrice <= 0){
                throw new BusinessException('竞标价格不能低于0元',ReceiveErrorCode::RECEIVE_COMPETITION_PRICE_ERROR);
            }
            if($receiver && $receiver['receive_state'] == OrderConst::HELPER_STATE_EMPLOYER_UN_CONFIRM){
                $receiverModel = $receiver;//重新报价
            }
            if($quotedPrice <= $taskOrder->origin_price){
                //throw new BusinessException("竞标价格不能低于雇主起始报价{$taskOrder->origin_price}");
            }
        }else{
            if($receiver){
                throw new BusinessException('您已经接过此任务了',TaskOrderErrorCode::STATE_RECEIVED);
            }
        }

        if($this->getHelperService()->countTodayCancelTotal($userId) >= OrderConst::HELPER_MAX_CANCEL_TODAY){
            $max = OrderConst::HELPER_MAX_CANCEL_TODAY;
            throw new BusinessException("您今天取消的任务超过{$max}次了，明天再来吧",ReceiveErrorCode::RECEIVE_CANCEL_LIMIT);
        }

        $stateOrderService = $this->getStateService();
        $quotedPrice = db_price($quotedPrice);
        $receiverModel->order_no = $orderNo;
        $receiverModel->user_id = $userId;
        $receiverModel->receive_state = OrderConst::HELPER_STATE_RECEIVE;
        $receiverModel->memo = $memo;
        $receiverModel->is_selected = 1;
        $receiverModel->order_type = $taskOrder['order_type'];
        if($taskOrder['order_type'] == OrderConst::TYPE_COMPETITION){
            $receiverModel->receive_state = OrderConst::HELPER_STATE_EMPLOYER_UN_CONFIRM;
            $receiverModel->quoted_price = $quotedPrice;
            $receiverModel->is_selected = 0;
        }else{
            $taskOrder->helper_user_id = $userId;
            $taskOrder->receive_time = Carbon::now();
        }
        $connection = DBConnection::getTradeConnection();
        try {
            $connection->beginTransaction();
            $stateOrderService->successHelperReceiver($taskOrder,$receiverModel);
            $stateOrderService->successEmployerReceiver($taskOrder);
            if($taskOrder['order_type'] != OrderConst::TYPE_COMPETITION){
                $this->getOrderSearchService()->deleteOrderSearch($orderNo);
            }
            $receiverModel->save();
            $taskOrder->save();
            $connection->commit();
            event(new TaskReceiveEvent($taskOrder['order_no'],$receiver['user_id'],$taskOrder['order_type']));
            return format_state_display($taskOrder,$receiver);
        }catch (\Exception $e){
            $connection->rollBack();
            $msgPrefix = ($taskOrder['order_type'] == OrderConst::TYPE_COMPETITION ? '竞价失败' : '抢单失败');
            \Log::error("{$msgPrefix} message:{$e->getMessage()}");
            throw new BusinessException($e->getMessage(),ReceiveErrorCode::RECEIVE_FAILED);
        }
    }

    /**
     * 交付任务
     *
     * @param $orderNo
     * @param $helpUserId
     * @return array
     * @throws BusinessException
     */
    public function delivery($orderNo,$helpUserId){
        if(empty($orderNo)){
            throw new BusinessException('订单号错误',TaskOrderErrorCode::SAVE_PARAM_CHECK_NO);
        }
        if($helpUserId <= 0){
            throw new BusinessException('用户信息错误',TaskOrderErrorCode::SAVE_PARAM_CHECK_USER);
        }
        $taskOrderModel = $this->getTaskOrderModel();
        $receiverModel = $this->getReceiveModel();
        $taskOrder = $taskOrderModel->getByOrderNo($orderNo);
        $stateOrderService = $this->getStateService();
        $refundService = $this->getRefundTaskOrderService();
        if(!$taskOrder){
            throw new BusinessException('任务不存在',TaskOrderErrorCode::SAVE_PARAM_CHECK_TASK_NOT_EXIST);
        }
        $receiver = $receiverModel->getOrderHelper($orderNo,$helpUserId);
        if(empty($receiver)){
            throw new BusinessException("您还没有任务，请去任务大厅抢单",TaskOrderErrorCode::STATE_NO_RECEIVE);
        }

        if($receiver['receive_state'] == OrderConst::HELPER_STATE_COMPLETE){
            throw new BusinessException('任务已完成',TaskOrderErrorCode::STATE_COMPLETE);
        }
        if($receiver['receive_state'] == OrderConst::HELPER_STATE_CANCEL){
            throw new BusinessException('任务已取消',TaskOrderErrorCode::STATE_CANCELED);
        }

        if(!in_array($receiver['receive_state'],[OrderConst::HELPER_STATE_RECEIVE,OrderConst::HELPER_STATE_REFUSE_DELIVERY])){
            throw new BusinessException("帮手在非接单或被拒绝交付状态下，不能交付",ReceiveErrorCode::DELIVERY_HELPER_UN_RECEIVE);
        }
        if(!in_array($taskOrder['order_state'], [OrderConst::EMPLOYER_STATE_RECEIVE,OrderConst::EMPLOYER_STATE_REFUSE_DELIVERY])){
            throw new BusinessException("任务在非接单或被拒绝交付状态下，不能交付",ReceiveErrorCode::DELIVERY_EMPLOYER_UN_RECEIVE);
        }

        $response = [
            'op_status' => 0,
            'diff_price' => 0,
            'diff_minutes' =>0,
            'diff_price_display' => 0,
            'tips' => '',
            'price_list'=>[]
        ];
        $validTime = valid_between_time($taskOrder['start_time'],$taskOrder['end_time']);
        $connection = DBConnection::getTradeConnection();
        try {
            $connection->beginTransaction();
            $this->getDeliveryRecordModel()->add($orderNo,$helpUserId);
            $response['diff_minutes'] = $validTime['diff_minutes'];
            if ($validTime['status'] != 2) {
                $response['op_status'] = 1;
                $stateOrderService->successDelivery($taskOrder,$receiver);
            }else {
                $refundablePrice = $refundService->getEmployerRefundablePrice($orderNo);
                $changePrice = overtime_compensate_price($refundablePrice['refundable_money'], $taskOrder['start_time'], $taskOrder['end_time']);//帮手按原价算
                $changeDisplayPrice = display_price($changePrice);
                $response['diff_price_display'] = $changeDisplayPrice;
                $response['diff_price'] = $changePrice;
                $response['tips'] = "您已经逾期交付{$validTime['diff_minutes']}分钟了，需要支付赔偿金{$changeDisplayPrice}元，已从任务报酬中代扣";
                $response['op_status'] = 1;
                $waterNo = UniqueNo::buildPriceWaterNo($receiver['user_id'], OrderConst::PRICE_CHANGE_HELPER_OVERTIME);
                $compensateService = $this->getCompensateService();
                $inoutLogService = $this->getInoutLogService();
                $payData = $this->addBeforePayLog($receiver, $taskOrder['order_name'], $waterNo, $changePrice, OrderConst::PRICE_CHANGE_HELPER_OVERTIME);
                $compensateService->compensate($receiver['user_id'], $taskOrder['user_id'], $taskOrder['origin_price'], $changePrice, PayConst::INOUT_OVERTIME_COMPENSATE, $payData['pay_no'], $orderNo);
                $inoutLogService->addInoutLog($receiver['user_id'], convert_negative_number($changePrice), PayConst::CHANNEL_BALANCE, PayConst::INOUT_OVERTIME_COMPENSATE, PayConst::SOURCE_TASK_ORDER, $orderNo, $taskOrder['helper_user_id']);
                $stateOrderService->successDelivery($taskOrder, $receiver);
            }
            $connection->commit();
            event(new TaskDeliveryEvent($receiver['order_no'],$receiver['user_id']));
            $format = format_state_display($taskOrder,$receiver);
            return array_merge($response,$format);
        }catch (\Exception $e){
            $connection->rollBack();
            \Log::error("交付失败 message:{$e->getMessage()}");
            throw new BusinessException($e->getMessage(),ReceiveErrorCode::DELIVERY_FAILED);
        }

    }

    /**
     * 取消任务
     *
     * @param $orderNo
     * @param $helpUserId
     * @param $cancelType
     * @param $cancelReason
     * @param array $attachmentList
     * @return array
     * @throws BusinessException
     */
    public function cancel($orderNo,$helpUserId,$cancelType,$cancelReason,array $attachmentList = []){
        if(empty($orderNo)){
            throw new BusinessException('订单号错误',TaskOrderErrorCode::SAVE_PARAM_CHECK_NO);
        }
        if($helpUserId <= 0){
            throw new BusinessException('用户信息错误',TaskOrderErrorCode::SAVE_PARAM_CHECK_USER);
        }
        if($cancelType <= 0){
            throw new BusinessException('请选择取消类型',TaskOrderErrorCode::CANCEL_PARAM_CHECK_CANCEL);
        }
        $taskOrderModel = $this->getTaskOrderModel();
        $receiverModel = $this->getReceiveModel();
        $taskOrder = $taskOrderModel->getByOrderNo($orderNo);
        $stateOrderService = $this->getStateService();
        $detailOrderService = $this->getDetailService();
        if(!$taskOrder){
            throw new BusinessException('任务不存在',TaskOrderErrorCode::SAVE_PARAM_CHECK_TASK_NOT_EXIST);
        }
        $receiver = $receiverModel->getOrderHelper($orderNo,$helpUserId);
        if(empty($receiver)){
            throw new BusinessException("您没有权限操作",TaskOrderErrorCode::PERMISSION_NOT_ALLOW);
        }

        if($receiver['receive_state'] == OrderConst::HELPER_STATE_COMPLETE){
            throw new BusinessException('任务已完成，不能取消',TaskOrderErrorCode::STATE_COMPLETE);
        }
        if($receiver['receive_state'] == OrderConst::HELPER_STATE_CANCEL){
            throw new BusinessException('任务已取消',TaskOrderErrorCode::STATE_CANCELED);
        }
        if($taskOrder['order_state'] == OrderConst::EMPLOYER_STATE_DELIVERED){
            throw new BusinessException('任务已交付，不能取消',TaskOrderErrorCode::STATE_DELIVERED);
        }
        $response = [
            'op_status' => 1,
            'diff_price' => 0,
            'diff_price_display' => 0,
            'tips' => '',
            'price_list'=>[]
        ];
        $connection = DBConnection::getTradeConnection();
        try{
            $connection->beginTransaction();
            $originReceiveState = $receiver['receive_state'];
            $this->addOrderCancel($orderNo,$helpUserId,UserConst::TYPE_HELPER,$cancelType,$cancelReason,$attachmentList);
            $stateOrderService->cancelHelper($receiver,$cancelType);
            $stateOrderService->reverseOrderReceive($taskOrder);
            $changePrice = cancel_compensate_price($originReceiveState, $taskOrder['origin_price'], UserConst::TYPE_HELPER, $taskOrder['start_time'], $taskOrder['end_time']);//帮手取消按原价算
            if($receiver['receive_state'] != OrderConst::HELPER_STATE_EMPLOYER_UN_CONFIRM && $changePrice > 0) {

                $accountBalance = $this->getAccountService()->getAccountByUserId($receiver['user_id'], 'available_balance');
                $changeDisplayPrice = display_price($changePrice);
                $taskOrderEntity = new TaskOrderEntity();
                $taskOrderEntity->orderNo = $orderNo;
                $taskOrderEntity->userId = $helpUserId;
                $taskOrderEntity->payPrice = 0;
                $taskOrderEntity->changePrice = $changePrice;//补差价到什么地方，待定
                $this->batchAddOrderService($taskOrderEntity, OrderConst::PRICE_OP_STATE_UN_HANDLE, OrderConst::PRICE_CHANGE_HELPER_CANCEL, OrderConst::INOUT_OUT);
                $response['tips'] = "任务取消中，您取消需要支付雇主赔偿金{$changeDisplayPrice}元";
                $response['diff_price_display'] = $changeDisplayPrice;
                $response['diff_price'] = $changePrice;
                if ($accountBalance >= $changePrice) {
                    $response['op_status'] = 1;
                    $response['tips'] = "任务取消中，您取消需要支付雇主赔偿金{$changeDisplayPrice}元，已从您的余额中扣除";
                    $priceChange = $detailOrderService->getLatestOrderPriceChangeType($orderNo, OrderConst::PRICE_CHANGE_HELPER_CANCEL);
                    $this->reduceBalance($receiver, $taskOrder['order_name'], $priceChange['water_no'], $changePrice, OrderConst::PRICE_CHANGE_HELPER_CANCEL);
                    $receiver->cancel_compensate_status = OrderConst::CANCEL_COMPENSATE_STATUS_COMPLETE;
                } else {
                    $response['op_status'] = 0;
                    $response['price_list'] = $detailOrderService->getLatestChangePayPrice($orderNo, OrderConst::PRICE_CHANGE_HELPER_CANCEL, true);
                    $receiver->cancel_compensate_status = OrderConst::CANCEL_COMPENSATE_STATUS_HAS;
                }
                $receiver->save();
            }
            $connection->commit();
            event(new TaskHelperCancelEvent($receiver['order_no'],$receiver['user_id']));
            event(new TaskReverseStartEvent($taskOrder['order_no']));
            $format = format_state_display($taskOrder,$receiver);
            return array_merge($response,$format);
        }catch (\Exception $e){
            $connection->rollBack();
            \Log::error("取消失败 message:{$e->getMessage()}");
            throw new BusinessException($e->getMessage(),ReceiveErrorCode::CANCEL_FAILED);
        }
    }

    /**
     * 取消报价
     *
     * @param $orderNo
     * @param $helpUserId
     * @return bool
     * @throws BusinessException
     * @throws \Exception
     */
    public function cancelQuoted($orderNo,$helpUserId){
        if(empty($orderNo)){
            throw new BusinessException('订单号错误',TaskOrderErrorCode::SAVE_PARAM_CHECK_NO);
        }
        if($helpUserId <= 0){
            throw new BusinessException('用户信息错误',TaskOrderErrorCode::SAVE_PARAM_CHECK_USER);
        }
        $taskOrderModel = $this->getTaskOrderModel();
        $receiverModel = $this->getReceiveModel();
        $taskOrder = $taskOrderModel->getByOrderNo($orderNo);
        if(!$taskOrder){
            throw new BusinessException('任务不存在',TaskOrderErrorCode::SAVE_PARAM_CHECK_TASK_NOT_EXIST);
        }
        $receiver = $receiverModel->getOrderHelper($orderNo,$helpUserId);
        if(empty($receiver)){
            throw new BusinessException("您没有权限操作",TaskOrderErrorCode::PERMISSION_NOT_ALLOW);
        }
        if($receiver['receive_state'] != OrderConst::HELPER_STATE_EMPLOYER_UN_CONFIRM){
            throw new BusinessException("任务不在待确认状态，不能取消报价",ReceiveErrorCode::CANCEL_QUOTED_UN_CONFIRM);
        }
        $this->addOrderCancel($orderNo,$helpUserId,UserConst::TYPE_HELPER,OrderConst::CANCEL_TYPE_HELPER_QUOTED,'帮手取消报价');
        $receiver->delete();
        return format_state_display($taskOrder,$receiver);
    }

    /**
     * 延迟报价
     *
     * @param $orderNo
     * @param $helpUserId
     * @param $deferMinute
     * @param $reason
     * @return array
     * @throws BusinessException
     */
    public function defer($orderNo,$helpUserId,$deferMinute,$reason = ''){
        if(empty($orderNo)){
            throw new BusinessException('订单号错误',TaskOrderErrorCode::SAVE_PARAM_CHECK_NO);
        }
        if($helpUserId <= 0){
            throw new BusinessException('用户信息错误',TaskOrderErrorCode::SAVE_PARAM_CHECK_USER);
        }

        if($deferMinute <= 0){
            throw new BusinessException('延期时间错误',ReceiveErrorCode::DEFER_TIME_ERROR);

        }
        $taskOrderModel = $this->getTaskOrderModel();
        $receiverModel = $this->getReceiveModel();
        $taskOrder = $taskOrderModel->getByOrderNo($orderNo);
        if(!$taskOrder){
            throw new BusinessException('任务不存在',TaskOrderErrorCode::SAVE_PARAM_CHECK_TASK_NOT_EXIST);
        }
        $receiver = $receiverModel->getOrderHelper($orderNo,$helpUserId);
        if(empty($receiver)){
            throw new BusinessException("您还没有任务，请去任务大厅抢单",TaskOrderErrorCode::STATE_NO_RECEIVE);
        }

        $deferExist = Defer::where(['user_id'=>$helpUserId,'order_no'=>$orderNo])->first();
        if($deferExist){
            throw new BusinessException("只有一次机会延期申请，您已经申请过了",ReceiveErrorCode::DEFER_ONLY_ONCE);
        }
        if($receiver['receive_state'] == OrderConst::HELPER_STATE_COMPLETE){
            throw new BusinessException('任务已完成',TaskOrderErrorCode::STATE_COMPLETE);
        }
        if($receiver['receive_state'] == OrderConst::HELPER_STATE_CANCEL){
            throw new BusinessException('任务已取消',TaskOrderErrorCode::STATE_CANCELED);
        }

        if(!in_array($receiver['receive_state'],[OrderConst::HELPER_STATE_RECEIVE,OrderConst::HELPER_STATE_REFUSE_DELIVERY])){
            throw new BusinessException("帮手在非接单或被拒绝交付状态下，不能延期",ReceiveErrorCode::DELIVERY_HELPER_UN_RECEIVE);
        }
        if(!in_array($taskOrder['order_state'], [OrderConst::EMPLOYER_STATE_RECEIVE,OrderConst::EMPLOYER_STATE_REFUSE_DELIVERY])){
            throw new BusinessException("任务在非接单或被拒绝交付状态下，不能延期",ReceiveErrorCode::DELIVERY_EMPLOYER_UN_RECEIVE);
        }

        $response = [
            'op_status' => 0,
            'diff_price' => 0,
            'diff_minutes' =>0,
            'diff_price_display' => 0,
            'tips' => '',
            'price_list'=>[]
        ];
        $validTime = valid_between_time($taskOrder['start_time'],$taskOrder['end_time']);
        if ($validTime['status'] != 2) {
            throw new BusinessException('任务还没有逾期，还不能延期',ReceiveErrorCode::DEFER_TIME_NOT);
        }
        $connection = DBConnection::getTradeConnection();
        try {
           // $connection->beginTransaction();
            $response['diff_minutes'] = $validTime['diff_minutes'];
            $deferEndAt = Carbon::parse($taskOrder['end_time'])->addMinutes($deferMinute);
            $deferModel = new Defer();
            $deferModel->order_no = $orderNo;
            $deferModel->user_id = $helpUserId;
            $deferModel->reason = $reason;
            $deferModel->last_end_at = $taskOrder['end_time'];
            $deferModel->defered_at = $deferEndAt;
            $deferModel->status = 0;
            $deferModel->defer_minutes = $deferMinute;
            $deferModel->save();
            //$connection->commit();
            return $response;
        }catch (\Exception $e){
           // $connection->rollBack();
            \Log::error("延期失败 message:{$e->getMessage()}");
            throw new BusinessException("",ReceiveErrorCode::DEFER_FAILED);
        }
    }

    /**
     * 获取任务预扣除费用
     *
     * @param $orderNo
     * @return array
     */
    public function getFee($orderNo){
        if(empty($orderNo)){
            return [];
        }

        $taskOrderModel = $this->getTaskOrderModel();
        $taskOrder = $taskOrderModel->getByOrderNo($orderNo);
        if(!$taskOrder){
            return [];
        }
        return $this->getFeeTaskService()->computeTaskFeeByOrderNo($orderNo,$taskOrder['user_id']);
    }
    /**
     * 检查任务是否延期超时交付赔偿金额
     *
     * @param $orderNo
     * @param $helpUserId
     * @return array
     * @throws BusinessException
     */
    public function checkHelperOvertimeMoney($orderNo,$helpUserId){
        if(empty($orderNo)){
            throw new BusinessException('订单号错误',TaskOrderErrorCode::SAVE_PARAM_CHECK_NO);
        }
        if($helpUserId <= 0){
            throw new BusinessException('用户信息错误',TaskOrderErrorCode::SAVE_PARAM_CHECK_USER);
        }
        $taskOrderModel = $this->getTaskOrderModel();
        $receiverModel = $this->getReceiveModel();
        $taskOrder = $taskOrderModel->getByOrderNo($orderNo);
        if(!$taskOrder){
            throw new BusinessException('任务不存在',TaskOrderErrorCode::SAVE_PARAM_CHECK_TASK_NOT_EXIST);
        }
        $receiver = $receiverModel->getOrderHelper($orderNo,$helpUserId);
        if(empty($receiver)){
            throw new BusinessException("您没有权限操作",TaskOrderErrorCode::PERMISSION_NOT_ALLOW);
        }
        $response = valid_between_time($taskOrder['start_time'],$taskOrder['end_time']);
        $compensatePrice = overtime_compensate_price($taskOrder['origin_price'],$taskOrder['start_time'],$taskOrder['end_time']);
        if($compensatePrice > 0){
            $response['compensate_price'] = display_price($compensatePrice);
            return $response;
        }else{
            return [];
        }
    }

    /**
     * 帮手取消任务赔偿金额
     *
     * @param $orderNo
     * @param $helpUserId
     * @return array
     * @throws BusinessException
     */
    public function checkHelperCancelMoney($orderNo,$helpUserId){
        if(empty($orderNo)){
            throw new BusinessException('订单号错误',TaskOrderErrorCode::SAVE_PARAM_CHECK_NO);
        }
        if($helpUserId <= 0){
            throw new BusinessException('用户信息错误',TaskOrderErrorCode::SAVE_PARAM_CHECK_USER);
        }
        $taskOrderModel = $this->getTaskOrderModel();
        $receiverModel = $this->getReceiveModel();
        $taskOrder = $taskOrderModel->getByOrderNo($orderNo);
        if(!$taskOrder){
            throw new BusinessException('任务不存在',TaskOrderErrorCode::SAVE_PARAM_CHECK_TASK_NOT_EXIST);
        }
        $receiver = $receiverModel->getOrderHelper($orderNo,$helpUserId);
        if(empty($receiver)){
            throw new BusinessException("您没有权限操作",TaskOrderErrorCode::PERMISSION_NOT_ALLOW);
        }
        $response = valid_between_time($taskOrder['start_time'],$taskOrder['end_time']);
        $compensatePrice = cancel_compensate_price($receiver['receive_state'],$taskOrder['origin_price'],UserConst::TYPE_HELPER,$taskOrder['start_time'],$taskOrder['end_time']);
        if($compensatePrice > 0){
            $response['compensate_price'] = display_price($compensatePrice);
            return $response;
        }else{
            return [];
        }
    }
    /**
     * @param Model $receive
     * @param $taskName
     * @param $waterNo
     * @param $payPrice
     * @param int $mainStepType
     * @param int $payState
     * @return mixed
     * @throws BusinessException
     */
    protected function formatBalancePayData(Model $receive,$taskName,$waterNo,$payPrice,$mainStepType = OrderConst::PRICE_CHANGE_HELPER_OVERTIME,$payState = PayConst::STATE_UN_PAY){
        $payData['user_id'] = $receive['user_id'];
        $payData['biz_no'] = $receive['order_no'];
        $payData['channel'] = PayConst::CHANNEL_BALANCE;
        $payData['pay_type'] = 'normal';
        $payData['biz_source'] = PayConst::SOURCE_TASK_ORDER;
        $payData['biz_sub_no'] = $waterNo;
        $payData['pay_price'] = $payPrice;
        if($mainStepType == OrderConst::PRICE_CHANGE_HELPER_OVERTIME) {
            $payData['body'] = $taskName .' 超时交付赔付';
        }elseif($mainStepType == OrderConst::PRICE_CHANGE_HELPER_CANCEL){
            $payData['body'] = $taskName. ' 帮手取消赔付';
        }else{
            $payData['body'] = $taskName;
        }
        $payData['pay_no'] = UniqueNo::buildPayNo($receive['user_id'],$mainStepType);
        $payData['pay_state'] = $payState;
        return $payData;
    }

    /**
     * @param Model $receive
     * @param $taskName
     * @param $waterNo
     * @param $payPrice
     * @param int $mainStepType
     * @return mixed
     * @throws BusinessException
     */
    protected function reduceBalance(Model $receive,$taskName,$waterNo,$payPrice,$mainStepType = OrderConst::PRICE_CHANGE_HELPER_CANCEL){
        $payData = $this->formatBalancePayData($receive,$taskName,$waterNo,$payPrice,$mainStepType,PayConst::STATE_UN_PAY);
        $this->getPayService()->addPayLog($payData);
        $this->getBalancePayment()->pay($payData['user_id'],$payData['pay_price']);
        $this->getPayTaskOrderService()->notifyPay($payData['pay_no'],$payData['channel'],$payData['pay_price'],'');
        return $payData;
    }


    /**
     *
     * @param Model $receive
     * @param $taskName
     * @param $waterNo
     * @param $payPrice
     * @param int $mainStepType
     * @return mixed
     * @throws BusinessException
     */
    protected function addBeforePayLog(Model $receive,$taskName,$waterNo,$payPrice,$mainStepType = OrderConst::PRICE_CHANGE_HELPER_OVERTIME){
        $payData = $this->formatBalancePayData($receive,$taskName,$waterNo,$payPrice,$mainStepType,PayConst::STATE_PAY);
        $this->getPayService()->addPayLog($payData);
        return $payData;
    }


    /**
     * @return BlackService
     */
    protected function getBlackBridge(){
        return new BlackBridge(new BlackService());
    }

    /**
     * @return CertificationService
     */
    protected function getCertificationBridge(){
        return new CertificationBridge(new CertificationService());
    }
}
