<?php


namespace App\Services\Trade\Order\Employer;


use App\Consts\DBConnection;
use App\Consts\ErrorCode\TaskOrderErrorCode;
use App\Consts\Trade\OrderConst;
use App\Consts\Trade\PayConst;
use App\Consts\Trade\RefundConst;
use App\Consts\UserConst;
use App\Events\TaskOrder\TaskEmployerCancelEvent;
use App\Exceptions\BusinessException;
use App\Services\Trade\Order\BaseTaskOrderService;
use Carbon\Carbon;

/**
 * 雇主取消任务
 *
 * Class CancelOrderService
 * @package App\Services\Trade\Order\Employer
 */
class CancelOrderService extends BaseTaskOrderService
{

    public function cancel($orderNo,$userId,$cancelType,$cancelReason,array $attachmentList = []){
        if(empty($orderNo)){
            throw new BusinessException('订单号错误',TaskOrderErrorCode::SAVE_PARAM_CHECK_NO);
        }
        if($userId <= 0){
            throw new BusinessException('用户信息错误',TaskOrderErrorCode::SAVE_PARAM_CHECK_USER);
        }
        if($cancelType <= 0){
            throw new BusinessException('请选择取消类型',TaskOrderErrorCode::CANCEL_PARAM_CHECK_CANCEL);
        }
        $taskOrderModel = $this->getTaskOrderModel();
        $receiverModel = $this->getReceiveModel();
        $stateService = $this->getStateService();
        $feeTaskService = $this->getFeeTaskService();
        $taskOrder = $taskOrderModel->getByOrderNo($orderNo);
        $helpUserId = $taskOrder['helper_user_id'];
        $refundService = $this->getRefundTaskOrderService();
        $receiver = null;
        if($helpUserId) {
            $receiver = $receiverModel->getOrderHelper($orderNo, $helpUserId);
        }
        if($taskOrder['order_state'] == OrderConst::EMPLOYER_STATE_COMPLETE){
            throw new BusinessException('任务已完成，不能取消',TaskOrderErrorCode::STATE_COMPLETE);
        }
        if($taskOrder['order_state'] == OrderConst::EMPLOYER_STATE_CANCEL){
            throw new BusinessException('任务已取消',TaskOrderErrorCode::STATE_CANCELED);
        }
        if($taskOrder['user_id'] != $userId){
            throw new BusinessException('您没有权限操作',TaskOrderErrorCode::PERMISSION_NOT_ALLOW);
        }
        $connection = DBConnection::getTradeConnection();
        $response = [
            'op_status' => 0,
            'refund_price' => 0,
            'refund_price_display' => 0,
            'tips' => ''
        ];
        try{
            $connection->beginTransaction();
            $this->addOrderCancel($orderNo,$userId,UserConst::TYPE_EMPLOYER,$cancelType,$cancelReason,$attachmentList);
            if($taskOrder['order_state'] <= OrderConst::EMPLOYER_STATE_UN_START){
                $stateService->cancelEmployer($taskOrder,$cancelType);
                $connection->commit();
                $format = format_state_display($taskOrder,$receiver);
                return array_merge($response,$format);
            }
            $refundServices = [];//任务未被接单之前是可以退还服务费的
            if(in_array($taskOrder['order_state'] , [OrderConst::EMPLOYER_STATE_UN_RECEIVE,OrderConst::EMPLOYER_STATE_UN_CONFIRM])){
                $refundServices = [OrderConst::SERVICE_PRICE_TYPE_FACE,OrderConst::SERVICE_PRICE_TYPE_INSURANCE,OrderConst::SERVICE_PRICE_TYPE_URGE];
            }
            $refundablePrice = $refundService->getEmployerRefundablePrice($orderNo,$refundServices);
            $refundableMoney = $refundablePrice['refundable_money'];
            $refundHelperMoney = 0;
            if(in_array($taskOrder['order_state'],[OrderConst::EMPLOYER_STATE_RECEIVE,OrderConst::EMPLOYER_STATE_DELIVERED,OrderConst::EMPLOYER_STATE_REFUSE_DELIVERY] )){
                $refundHelperMoney = $feeTaskService->computeEmployerCancel($orderNo,$refundableMoney);
            }
            $refundableEmployerMoney = bcsub($refundableMoney, $refundHelperMoney);
            $stateService->cancelAllHelper($orderNo,$cancelType,0);
            if($refundableEmployerMoney > 0) {
                $refundService->refundByEmployer($orderNo, $refundableEmployerMoney, PayConst::INOUT_TASK_REFUND,$refundServices);
            }
            $taskOrder->refund_time = Carbon::now();
            $taskOrder->refund_state = RefundConst::STATE_ALL;
            $taskOrder->save();
            $this->getOrderSearchService()->deleteOrderSearch($orderNo);
            $stateService->cancelEmployer($taskOrder,$cancelType);
            $connection->commit();
            $response['op_status'] = 1;
            $response['refund_price'] = $refundableEmployerMoney;
            $response['display_refund_price'] = display_price($refundableEmployerMoney);
            event(new TaskEmployerCancelEvent($taskOrder['order_no']));
            $format = format_state_display($taskOrder,$receiver);
            return array_merge($response,$format);
        }catch (\Exception $e){
           $connection->rollBack();
            \Log::error("任务取消失败 message:{$e->getMessage()}");
            throw new BusinessException($e->getMessage(),TaskOrderErrorCode::CANCEL_FAILED);
        }


    }

    /**
     * 检查退还还给帮手的钱
     *
     * @param $orderNo
     * @param $employerUserId
     * @return array
     * @throws BusinessException
     */
    public function checkEmployerCancelMoney($orderNo,$employerUserId){
        if(empty($orderNo)){
            throw new BusinessException('订单号错误',TaskOrderErrorCode::SAVE_PARAM_CHECK_NO);
        }
        if($employerUserId <= 0){
            throw new BusinessException('用户信息错误',TaskOrderErrorCode::SAVE_PARAM_CHECK_USER);
        }
        $taskOrderModel = $this->getTaskOrderModel();
        $taskOrder = $taskOrderModel->getByOrderNo($orderNo);
        if(!$taskOrder){
            throw new BusinessException('任务不存在',TaskOrderErrorCode::SAVE_PARAM_CHECK_TASK_NOT_EXIST);
        }
        if($taskOrder['user_id'] != $employerUserId){
            throw new BusinessException("您没有权限操作",TaskOrderErrorCode::PERMISSION_NOT_ALLOW);
        }
        $response = valid_between_time($taskOrder['start_time'],$taskOrder['end_time']);
        $compensatePrice = cancel_compensate_price($taskOrder['order_state'],$taskOrder['pay_price'],UserConst::TYPE_EMPLOYER,$taskOrder['start_time'],$taskOrder['end_time']);
        if($compensatePrice > 0){
            $response['compensate_price'] = display_price($compensatePrice);
            return $response;
        }else{
            return [];
        }
    }
}
