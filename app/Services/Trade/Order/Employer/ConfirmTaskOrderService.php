<?php


namespace App\Services\Trade\Order\Employer;


use App\Consts\DBConnection;
use App\Consts\ErrorCode\TaskOrderErrorCode;
use App\Consts\Trade\OrderConst;
use App\Consts\Trade\PayConst;
use App\Events\TaskOrder\TaskCompleteEvent;
use App\Events\TaskOrder\TaskConfirmReceiveEvent;
use App\Events\TaskOrder\TaskRefuseDeliveryEvent;
use App\Exceptions\BusinessException;
use App\Models\Trade\Entity\TaskOrderEntity;
use App\Models\Trade\Order\Defer;
use App\Services\Trade\Order\BaseTaskOrderService;

/**
 * 雇主确认任务完成
 *
 * Class ConfirmTaskOrderService
 * @package App\Services\Trade\Order\Employer
 */
class ConfirmTaskOrderService extends BaseTaskOrderService
{

    public function confirmHelper($orderNo,$userId,$helperUid){
        if($helperUid <= 0){
            throw new BusinessException("请选择帮手",TaskOrderErrorCode::CONFIRM_HELPER);
        }
        if($userId <= 0){
            throw new BusinessException("请登录后操作",TaskOrderErrorCode::SAVE_PARAM_CHECK_TASK_NOT_EXIST);
        }
        if(empty($orderNo)){
            throw new BusinessException("任务信息错误",TaskOrderErrorCode::SAVE_PARAM_CHECK_NO);
        }
        $taskOrderModel = $this->getTaskOrderModel();
        $receiveOrderModel = $this->getReceiveModel();
        $taskOrder = $taskOrderModel->getByOrderNo($orderNo);
        $stateOrderService = $this->getStateService();
        $detailOrderService = $this->getDetailService();
        if(!$taskOrder){
            throw new BusinessException('任务不存在',TaskOrderErrorCode::SAVE_PARAM_CHECK_TASK_NOT_EXIST);
        }
        if(in_array($taskOrder['order_state'],[OrderConst::EMPLOYER_STATE_UN_START])){
            throw new BusinessException('您还未付款，不能选择帮手',TaskOrderErrorCode::CONFIRM_UN_PAY);
        }
        if(!in_array($taskOrder['order_state'],[OrderConst::EMPLOYER_STATE_UN_CONFIRM])){
            throw new BusinessException('您已经选过帮手了',TaskOrderErrorCode::CONFIRM_SELECTED);
        }
        if($taskOrder['user_id'] != $userId){
            throw new BusinessException("您没有权限操作",TaskOrderErrorCode::PERMISSION_NOT_ALLOW);
        }
        if($taskOrder['order_type'] != OrderConst::TYPE_COMPETITION){
            throw new BusinessException("任务信息有误",TaskOrderErrorCode::CONFIRM_INFO_ERROR);
        }

        $receiver = $receiveOrderModel->getOrderHelper($orderNo,$helperUid);
        if(empty($receiver) || $receiver['receive_state'] == OrderConst::HELPER_STATE_CANCEL){
            throw new BusinessException("您选择的帮手已经取消了任务",TaskOrderErrorCode::CONFIRM_HELPER_ERROR);
        }
        $validTime = valid_between_time($taskOrder['start_time'],$taskOrder['end_time']);
        if($validTime['status'] == OrderConst::TIME_EXPIRED){
            throw new BusinessException("任务已经过期了，请修改过期时间",TaskOrderErrorCode::CONFIRM_TASK_EXPIRED);
        }
        $connection = DBConnection::getTradeConnection();
        try {
            $response = [
                'op_status' => 0,
                'diff_price' => 0,
                'diff_price_display' => 0,
                'tips' => ''
            ];
            $connection->beginTransaction();
            $quotedPrice = $receiver['quoted_price'];
            $payPrice = $taskOrder['pay_price'];
            $diffPrice = $quotedPrice - $payPrice;
            $quotedPriceDisplay = display_price($quotedPrice);
            $payPriceDisplay =  display_price($payPrice);
            $diffPriceDisplay = display_price($diffPrice);
            $response['diff_price'] = $diffPrice;
            $response['diff_price_display'] = $diffPriceDisplay;
            $response['price_list'] = [];
            if($diffPrice <= 0)
            {
                //雇主支付有余钱，直接成功，余钱任务完成后退回用户支付账户/余额
                //不要取消其它帮手的接单，防止帮手取消任务单后，雇主没有选择的帮手，任务完成时取消
                $stateOrderService->successConfirmReceiver($taskOrder,$receiver);
                $this->getOrderSearchService()->deleteOrderSearch($orderNo);
                $connection->commit();
                event(new TaskConfirmReceiveEvent($taskOrder['order_no'],$receiver['user_id'],$diffPrice));
                $response['op_status'] = 1;
                $response['tips'] = "您的任务单超付了{$response['diff_price_display']}元，平台在任务完成后原路退回到你的支付账户";
                $format = format_state_display($taskOrder,$receiver);
                return array_merge($response,$format);
            }
            $taskOrder->helper_user_id = $helperUid;
            $taskOrder->save();
            //补差价
            $taskOrderEntity = new TaskOrderEntity();
            $taskOrderEntity->orderNo = $orderNo;
            $taskOrderEntity->userId = $userId;
            $taskOrderEntity->payPrice = 0;
            $taskOrderEntity->changePrice = $diffPrice;
            $this->batchAddOrderService($taskOrderEntity,OrderConst::PRICE_OP_STATE_UN_HANDLE,OrderConst::PRICE_CHANGE_CONFIRM);
            $response['tips'] = "帮手报价{$quotedPriceDisplay}元，您已经支付{$payPriceDisplay}元，还需支付{$diffPriceDisplay}元";
            $response['price_list'] = $detailOrderService->getLatestChangePayPrice($orderNo,OrderConst::PRICE_CHANGE_CONFIRM,true);
            $connection->commit();
            $format = format_state_display($taskOrder,$receiver);
            return array_merge($response,$format);
        }catch (\Exception $e){
            $connection->rollBack();
            \Log::error("选择帮手失败 message:{$e->getMessage()}",'');
            throw new BusinessException($e->getMessage(),TaskOrderErrorCode::CONFIRM_TASK_FAILED);
        }
    }

    public function confirmComplete($orderNo,$userId){
        if(empty($orderNo)){
            throw new BusinessException("任务信息错误",TaskOrderErrorCode::SAVE_PARAM_CHECK_NO);
        }
        $taskOrderModel = $this->getTaskOrderModel();
        $taskOrder = $taskOrderModel->getByOrderNo($orderNo);
        $stateOrderService = $this->getStateService();
        if(!$taskOrder){
            throw new BusinessException('任务不存在',TaskOrderErrorCode::SAVE_PARAM_CHECK_TASK_NOT_EXIST);
        }
        if($taskOrder['helper_user_id'] <= 0){
            throw new BusinessException('任务没有接单人',TaskOrderErrorCode::STATE_NO_RECEIVE);
        }
        if($taskOrder['user_id'] != $userId){
            throw new BusinessException('您没有权限操作',TaskOrderErrorCode::PERMISSION_NOT_ALLOW);
        }
        if($taskOrder['order_state'] != OrderConst::EMPLOYER_STATE_DELIVERED){
            throw new BusinessException('任务不再交付状态',TaskOrderErrorCode::STATE_NO_DELIVERED);
        }
        $receiver = $this->getValidReceiverByOrderNo($orderNo,$taskOrder['helper_user_id']);
        if(empty($receiver) || $receiver['receive_state'] != OrderConst::HELPER_STATE_DELIVERED){
            throw new BusinessException('帮手还没有交付任务，不能确认任务完成',TaskOrderErrorCode::STATE_NO_DELIVERED);
        }
        $connection = DBConnection::getTradeConnection();
        try{
            $feeTaskOrderService = $this->getFeeTaskService();
            $compensateService = $this->getCompensateService();
            $refundService = $this->getRefundTaskOrderService();
            $connection->beginTransaction();
            //帮手逾期交付，给雇主结算
            $feeTaskOrderService->orderCompleteSettled($orderNo);
            $compensatePriceList = $compensateService->settled($orderNo,PayConst::INOUT_OVERTIME_COMPENSATE);
            //帮手超时赔付的钱，直接从任务收入里面扣
            $overTimeCompensatePrice = $compensatePriceList[PayConst::INOUT_OVERTIME_COMPENSATE] ?? 0;
            //给帮手结算
            $refundablePrice = $refundService->getEmployerRefundablePrice($orderNo);
            $helperIncome = bcsub($refundablePrice['helper_income'],$overTimeCompensatePrice);
            if($taskOrder['order_type'] == OrderConst::TYPE_COMPETITION) {
                //取消其它帮手的任务单
                $stateOrderService->cancelAllHelper($orderNo,OrderConst::CANCEL_TYPE_EMPLOYER_COMPETITION_FAIL,$receiver['user_id']);
                //竞价订单，帮手报价低于支付价，剩余钱退给雇主
                $refundOverPayPrice = 0;
                if($receiver['quoted_price'] > 0 && $receiver['quoted_price'] < $taskOrder['pay_price']){
                    $refundOverPayPrice = bcsub($refundablePrice['refundable_money'],$receiver['quoted_price']);
                    $refundOverPayPrice = bcadd($refundOverPayPrice,$refundablePrice['fee_total_money']);//手续费，帮手承担了
                    $helperIncome = bcsub($receiver['quoted_price'],$refundablePrice['fee_total_money']);//用户报价低于支付价
                }
                if($refundOverPayPrice > 0){
                    $this->getRefundTaskOrderService()->refundByEmployer($orderNo,$refundOverPayPrice,PayConst::INOUT_EMPLOYER_OVERPAY_REFUND);
                }
            }
            $this->settledHelper($taskOrder,$receiver,$helperIncome);
            //超时赔付的钱直接退给雇主
            if($overTimeCompensatePrice > 0){
                //退款一定不能大于可退金额
                $overTimeCompensatePrice = ($overTimeCompensatePrice < $refundablePrice['refundable_money']) ? $overTimeCompensatePrice : $refundablePrice['refundable_money'];
                $this->getRefundTaskOrderService()->refundByEmployer($orderNo,$overTimeCompensatePrice,PayConst::INOUT_OVERTIME_EMPLOYER_COMPENSATE);
            }
            $stateOrderService->successComplete($taskOrder,$receiver);
            $connection->commit();
            event(new TaskCompleteEvent($taskOrder['order_no']));
            return  format_state_display($taskOrder,$receiver);
        }catch (\Exception $e){
            $connection->rollBack();
            \Log::error("任务完成失败 message:{$e->getMessage()}");
            throw new BusinessException($e->getMessage(),TaskOrderErrorCode::COMPLETE_FAILED);
        }

    }


    /**
     * @param int $orderNo
     * @param int $userId
     * @param int $refuseType
     * @param string  $refuseReason
     * @return array
     * @throws BusinessException
     */
    public function refuseDelivery($orderNo,$userId,$refuseType = OrderConst::REFUSE_TYPE_EMPLOYER_LEAVE,$refuseReason = ''){
        if(empty($orderNo)){
            throw new BusinessException("任务信息错误",TaskOrderErrorCode::SAVE_PARAM_CHECK_NO);
        }
        $taskOrderModel = $this->getTaskOrderModel();
        $taskOrder = $taskOrderModel->getByOrderNo($orderNo);
        $stateOrderService = $this->getStateService();
        if(!$taskOrder){
            throw new BusinessException('任务不存在',TaskOrderErrorCode::SAVE_PARAM_CHECK_TASK_NOT_EXIST);
        }
        if($taskOrder['helper_user_id'] <= 0){
            throw new BusinessException('任务没有接单人',TaskOrderErrorCode::STATE_NO_RECEIVE);
        }
        if($taskOrder['user_id'] != $userId){
            throw new BusinessException('您没有权限操作',TaskOrderErrorCode::PERMISSION_NOT_ALLOW);
        }
        if($taskOrder['order_state'] != OrderConst::EMPLOYER_STATE_DELIVERED){
            throw new BusinessException('任务不再交付状态',TaskOrderErrorCode::STATE_NO_DELIVERED);
        }

        $receiver = $this->getValidReceiverByOrderNo($orderNo,$taskOrder['helper_user_id']);
        if(empty($receiver) || $receiver['receive_state'] != OrderConst::HELPER_STATE_DELIVERED){
            throw new BusinessException('帮手还没有交付任务，不能确认任务完成',TaskOrderErrorCode::STATE_NO_DELIVERED);
        }
        $connection = DBConnection::getTradeConnection();
        try{
            $connection->beginTransaction();
            $stateOrderService->refuseDelivery($taskOrder,$receiver);
            $this->getDeliveryRecordModel()->refuseRecord($taskOrder['helper_user_id'],$orderNo,$refuseType,$refuseReason);
            $connection->commit();
            event(new TaskRefuseDeliveryEvent($orderNo));
            return format_state_display($taskOrder,$receiver);
        }catch (\Exception $e){
            $connection->rollBack();
            \Log::error("任务拒绝失败 message:{$e->getMessage()}");
            throw new BusinessException($e->getMessage(),TaskOrderErrorCode::REFUSE_DELIVERED_FAILED);
        }
    }

    public function confirmDefer($orderNo,$userId,$status){
        if(empty($orderNo)){
            throw new BusinessException("任务信息错误",TaskOrderErrorCode::SAVE_PARAM_CHECK_NO);
        }
        $taskOrderModel = $this->getTaskOrderModel();
        $taskOrder = $taskOrderModel->getByOrderNo($orderNo);
        if(!$taskOrder){
            throw new BusinessException('任务不存在',TaskOrderErrorCode::SAVE_PARAM_CHECK_TASK_NOT_EXIST);
        }
        if($taskOrder['helper_user_id'] <= 0){
            throw new BusinessException('任务没有接单人',TaskOrderErrorCode::STATE_NO_RECEIVE);
        }
        if($taskOrder['user_id'] != $userId){
            throw new BusinessException('您没有权限操作',TaskOrderErrorCode::PERMISSION_NOT_ALLOW);
        }
        if($taskOrder['order_state'] != OrderConst::EMPLOYER_STATE_RECEIVE){
            throw new BusinessException('任务不在接单状态',TaskOrderErrorCode::STATE_NO_DELIVERED);
        }
        $receiver = $this->getValidReceiverByOrderNo($orderNo,$taskOrder['helper_user_id']);
        if(empty($receiver) || $receiver['receive_state'] != OrderConst::HELPER_STATE_RECEIVE){
            throw new BusinessException('任务不在接单状态',TaskOrderErrorCode::STATE_NO_DELIVERED);
        }
        if(!in_array($status,[1,2])){
            throw new BusinessException('延期状态错误',TaskOrderErrorCode::DEFER_STATE_ERROR);
        }
        $defer = $this->getDetailService()->getOrderDefersByOrderNo($orderNo,$taskOrder['helper_user_id']);
        if(empty($defer)){
            throw new BusinessException('没有任务延期申请',TaskOrderErrorCode::DEFER_NOT_EXIST);
        }

        $connection = DBConnection::getTradeConnection();
        try{
            $connection->beginTransaction();
            $defer->status = $status;
            $taskOrder->end_time = $defer['defered_at'];
            $defer->save();
            $taskOrder->save();
            $connection->commit();
        }catch (\Exception $e){
            $connection->rollBack();
            \Log::error("任务延期操作失败 message:{$e->getMessage()}");
        }
    }
    /**
     * @param $taskOrder
     * @param $receiver
     * @param $helperMoney
     * @throws BusinessException
     */
    public function settledHelper($taskOrder,$receiver,$helperMoney){
        if($helperMoney <= 0){
            \Log::error("任务{$taskOrder->order_no} 超时严重，钱已被扣完");
            return;
        }
        $payLog = $this->getPayService()->getTaskOrderMainPayLog($taskOrder['order_no']);
        $this->getBalancePayment()->add($receiver['user_id'],$helperMoney);
        $this->getInoutLogService()->addInoutLog($receiver['user_id'],$helperMoney,$payLog['channel'],PayConst::INOUT_HELPER_COMPLETE,PayConst::SOURCE_TASK_ORDER,$taskOrder['order_no'],0,$payLog['pay_no']);
    }


}
