<?php


namespace App\Services\Trade\Pay;


use App\Consts\DBConnection;
use App\Consts\ErrorCode\PayErrorCode;
use App\Consts\Trade\FeeConst;
use App\Consts\Trade\OrderConst;
use App\Consts\Trade\PayConst;
use App\Events\TaskOrder\TaskConfirmReceiveEvent;
use App\Events\TaskOrder\TaskDeliveryEvent;
use App\Events\TaskOrder\TaskHelperCancelEvent;
use App\Events\TaskOrder\TaskHelperCompensatePayEvent;
use App\Events\TaskOrder\TaskReverseStartEvent;
use App\Events\TaskOrder\TaskStartEvent;
use App\Exceptions\BusinessException;
use App\Models\Trade\Order\PriceChange;
use App\Models\Trade\Order\Service;
use App\Services\Trade\Order\BaseTaskOrderService;
use App\Services\Trade\Order\Employer\DetailTaskOrderService;
use App\Utils\UniqueNo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Yansongda\LaravelPay\Facades\Pay;

/**
 * 雇主支付任务报酬服务层
 *
 * Class PayTaskOrderService
 * @package App\Services\Trade\Pay
 */
class PayTaskOrderService extends BaseTaskOrderService
{

    /**
     * @param $userId
     * @param $channel
     * @param $payType
     * @param $payPrice
     * @param $businessStepType
     * @param $orderNo
     * @param string $platformOpenId
     * @param string $payPassword
     * @return array
     * @throws BusinessException
     */
    public function pay($userId,$channel,$payType,$payPrice,$businessStepType,$orderNo,$platformOpenId = '',$payPassword = ''){
            $return = [
                    'pay_state' => PayConst::STATE_UN_PAY,
                    'third_pay_data' => []
            ];
            $payData = $this->payPreCheck($userId,$channel,$payType,$payPrice,$businessStepType,$orderNo);
            try {
                if ($channel == PayConst::CHANNEL_ALIPAY) {
                    $order = [
                        'out_trade_no' => $payData['pay_no'],
                        'total_amount' => $payPrice,
                        'subject' => $payData['body'],
                    ];
                    /** @var Response $response */
                    $response = Pay::alipay()->{$payType}($order);
                    $this->innerPay($payData);
                    $return['third_pay_data'] = $response->getContent();
                } elseif ($channel == PayConst::CHANNEL_WECHAT) {
                    $order = [
                        'out_trade_no' => $payData['pay_no'],
                        'total_fee' => $payData['pay_price'],
                        'body' => $payData['body'],
                        'openid' => $platformOpenId
                    ];
                    /** @var Response $response */
                    $response = Pay::wechat()->{$payType}($order);
                    $this->innerPay($payData);
                    $return['third_pay_data'] = $response->getContent();
                } elseif ($channel == PayConst::CHANNEL_BALANCE) {
                    $this->innerPay($payData);
                    $return['third_pay_data'] = $this->balancePay($payData,$payPassword);
                    $return['pay_state'] = PayConst::STATE_PAY;
                }
                return $return;
            }catch (\Exception $e){
                Log::error("pay error notify  message:{$e->getMessage()} pay-type:{$channel}-{$payType} user:{$userId}");
                throw new BusinessException($e->getMessage(),PayErrorCode::PAY_FAILED);
            }
    }


    /**
     * @param $userId
     * @param $channel
     * @param $payType
     * @param $payPrice
     * @param $businessStepType
     * @param $orderNo
     * @return mixed
     * @throws BusinessException
     */
    public function payPreCheck($userId,$channel,$payType,$payPrice,$businessStepType,$orderNo){
        if(empty($orderNo)){
            throw new BusinessException("任务单号为空",PayErrorCode::CHECK_TASK_ORDER_NO);
        }
        $payPrice = db_price($payPrice);
        $detailOrderService = new DetailTaskOrderService();
        $taskOrder = $detailOrderService->getOrder($orderNo);
        $mainOrderStepTypeList = OrderConst::getMainPriceChangeTypeList();
        $payTypeList = PayConst::getPayTypeList();
        $payTypeFlipList = array_flip($payTypeList);
        $channelList = array_keys(PayConst::getChannelList());
        if(empty($taskOrder)){
            throw new BusinessException("任务单不存在",PayErrorCode::CHECK_TASK_NOT_EXIST);
        }
        $mainChangeType = $detailOrderService->getLatestOrderPriceChangeType($orderNo,$businessStepType);
        if($mainChangeType != OrderConst::PRICE_CHANGE_HELPER_CANCEL && in_array($taskOrder['order_state'],OrderConst::employerUnPayStateList())){
            throw new BusinessException("任务正在进行中或者已完成，不能再次支付",PayErrorCode::CHECK_TASK_STATE_UN_PAY);
        }
        //帮手支付时，订单已取消成功，只有在取消状态，才能支付赔偿
        if($mainChangeType == OrderConst::PRICE_CHANGE_HELPER_CANCEL && $taskOrder['order_state'] != OrderConst::HELPER_STATE_CANCEL){
            throw new BusinessException("任务不在取消状态，不能支付",PayErrorCode::PAY_FAILED);
        }
        if(in_array($businessStepType,$mainOrderStepTypeList)){
            throw new BusinessException("任务支付步骤错误",PayErrorCode::CHECK_TASK_STEP_ERROR);
        }
        if($mainChangeType['op_state'] == PayConst::STATE_PAY){
            throw new BusinessException("您已经支付成功，不要重复支付",PayErrorCode::CHECK_TASK_NOT_REPEAT);
        }
        $payPriceList = $detailOrderService->getLatestChangePayPrice($orderNo,$businessStepType);
        if(empty($payPriceList)){
            throw new BusinessException("价格错误，请返回到修改/新增处任务重新支付",PayErrorCode::CHECK_TASK_PRICE_ERROR);
        }

        if(array_sum($payPriceList) != $payPrice){
            throw new BusinessException("价格错误，请返回到修改/新增处任务重新支付",PayErrorCode::CHECK_TASK_PRICE_ERROR);
        }
        if($mainChangeType['user_id'] != $userId){
            throw new BusinessException("您不能支付其它人的任务单",PayErrorCode::CHECK_NOT_PERMISSION);
        }
        if(!in_array($payType,$payTypeFlipList)){
            throw new BusinessException("支付方式不支持",PayErrorCode::CHECK_PAY_WAY_NOT_SUPPORT);
        }
        if(!in_array($channel,$channelList)){
            throw new BusinessException("支付渠道不支持",PayErrorCode::CHECK_CHANNEL_NOT_SUPPORT);
        }
        $userService = $this->getUserService();
        $user = $userService->user($userId);
        if(!$user){
            throw new BusinessException('用户不存在',PayErrorCode::CHECK_USER_ERROR);
        }

        $mainChange = $detailOrderService->getLatestOrderPriceChangeType($orderNo,$businessStepType);
        $payData['user_id'] = $userId;
        $payData['channel'] = $channel;
        $payData['pay_type'] = $payType;
        $payData['biz_no'] = $orderNo;
        $payData['biz_source'] = PayConst::SOURCE_TASK_ORDER;
        $payData['biz_sub_no'] = $mainChange['water_no'];
        $payData['pay_price'] = $payPrice;
        $payData['body'] = $user['user_name'].'-'.$taskOrder['order_name'];
        $payData['pay_no'] = UniqueNo::buildPayNo($userId,$businessStepType);
        $payData['pay_state'] = PayConst::STATE_UN_PAY;
        return $payData;
    }

    /**
     * @param array $payData
     * @throws BusinessException
     */
    public function innerPay(array $payData){
        $this->getPayService()->addPayLog($payData);
    }


    public function balancePay(array $payData,$payPassword = ''){

        try{
            $payment = $this->getBalancePayment();
            $passwordRight = $payment->verifyPayPassword($payData['user_id'],$payPassword);
            if(!$passwordRight){
                throw new BusinessException("支付密码错误",PayErrorCode::BALANCE_PASSWORD_ERROR);
            }
            $response = $payment->pay($payData['user_id'],$payData['pay_price']);
            //更新订单状态
            if(empty($response)){
                throw new BusinessException("支付失败",PayErrorCode::BALANCE_PAY_FAILED);
            }
            $this->notifyPay($payData['pay_no'],$payData['channel'],$payData['pay_price'],'');
            return $payData;
        }catch (\Exception $e){
            \Log::error("余额支付失败哦 bizNo:{$payData['pay_no']}  message:{$e->getMessage()}");
            DB::rollBack();
            throw new BusinessException($e->getMessage(),PayErrorCode::BALANCE_PAY_FAILED);
        }
    }


    public function notifyPay($payNo,$channel,$notifyPrice,$outTradeNo = ''){
        $connection = DBConnection::getTradeConnection();
        try {
            $connection->beginTransaction();
            $detailOrderService = $this->getDetailService();
            $stateOrderService = $this->getStateService();
            $inoutLogService = $this->getInoutLogService();
            $feeTaskService = $this->getFeeTaskService();
            $payLog = (new \App\Models\Trade\Pay\Pay())->getPayLogByPayNo($payNo);
            if (empty($payLog)) {
                Log::error("支付记录不存在 payNo: {$payNo}");
                throw new BusinessException("支付记录不存",PayErrorCode::NOTIFY_PAY_NOT_EXIST);
            }
            $orderNo = $payLog['biz_no'];
            $taskOrder = $this->getTaskOrderModel()->getByOrderNo($orderNo);
            $priceChange = $detailOrderService->getChangePriceByWaterNo($payLog['biz_sub_no']);
            if (empty($priceChange['pay_price_list'])) {
                Log::error("价格变更历史不存在 orderNo:{$payLog['biz_no']} waterNo:{$payLog['biz_sub_no']}");
                throw new BusinessException("价格异常",PayErrorCode::NOTIFY_PAY_PRICE_ERROR);
            }
            $realPayPrice = $priceChange['pay_price_list'];
            $priceChangeSumPrice = array_sum($realPayPrice);
            $mainPayType = $priceChange['main_pay_type'];
            $negativePrice = convert_negative_number($notifyPrice);
            if ($notifyPrice != $priceChangeSumPrice) {
                Log::error("价格回调异常 orderNo:{$orderNo} waterNo:{$payLog['biz_sub_no']} channel:{$channel} notifyPrice:{$notifyPrice} price:{$priceChangeSumPrice}");
            }
            $feeLogList = [];
            $this->paySuccess($payLog,$outTradeNo);
            $receiver = null;
            if(in_array($mainPayType,[OrderConst::PRICE_CHANGE_ORDER_PAY,OrderConst::PRICE_CHANGE_MAKE_UP,OrderConst::PRICE_CHANGE_CONFIRM])){//雇主付款
                $this->addService($realPayPrice,$taskOrder);
                if ($mainPayType == OrderConst::PRICE_CHANGE_ORDER_PAY) {
                    $stateOrderService->successEmployerFirstPay($taskOrder);
                    $this->getOrderSearchService()->saveOrderSearch($orderNo);
                } else if($mainPayType == OrderConst::PRICE_CHANGE_MAKE_UP){
                    $this->appendPayPrice($taskOrder,$realPayPrice['pay_price']);
                    $this->getOrderSearchService()->saveOrderSearch($orderNo);
                } else if($mainPayType == OrderConst::PRICE_CHANGE_CONFIRM){
                    $this->appendPayPrice($taskOrder,$realPayPrice['pay_price']);
                    $receiver = $this->getReceiveModel()->getOrderHelper($orderNo,$taskOrder['helper_user_id']);
                    $stateOrderService->successConfirmReceiver($taskOrder,$receiver);
                    $this->getOrderSearchService()->deleteOrderSearch($orderNo);
                }
                $feeLogList = $feeTaskService->computeTaskFee($payNo,false,false);
                $inoutLogService->addInoutLog($taskOrder['user_id'],$negativePrice,$channel,PayConst::INOUT_PAY,PayConst::SOURCE_TASK_ORDER,$orderNo,0,$payLog['pay_no']);
            }elseif($mainPayType == OrderConst::PRICE_CHANGE_HELPER_CANCEL){
                $receiver = $this->getReceiveModel()->getOrderHelper($orderNo,$payLog['user_id']);
                $receiver->cancel_compensate_status = OrderConst::CANCEL_COMPENSATE_STATUS_COMPLETE;
                $receiver->save();
                $feeLogList = $feeTaskService->computeHelpCancel($payNo);
                $inoutLogService->addInoutLog($receiver['user_id'],$negativePrice,$channel,PayConst::INOUT_HELPER_CANCEL_COMPENSATE,PayConst::SOURCE_TASK_ORDER,$orderNo,"0,{$taskOrder['user_id']}",$payLog['pay_no']);
                $this->getOrderSearchService()->saveOrderSearch($orderNo);
            }
            $payLog->third_fee = $feeLogList[FeeConst::TYPE_TRADE] ?? 0;
            $payLog->platform_fee = $feeLogList[FeeConst::TYPE_SERVICE] ?? 0;
            $payLog->save();
            $connection->commit();
            //通过Laravel 事件和队列将非核心业务事件化，不影响整体核心功能流程及事务
            if($mainPayType == OrderConst::PRICE_CHANGE_HELPER_CANCEL){
                 event(new TaskHelperCompensatePayEvent($orderNo,$payLog['user_id']));
            }elseif($mainPayType == OrderConst::PRICE_CHANGE_ORDER_PAY){
                event(new TaskStartEvent($taskOrder['order_no'],$notifyPrice));
            }elseif($mainPayType == OrderConst::PRICE_CHANGE_CONFIRM && $receiver){
                event(new TaskConfirmReceiveEvent($taskOrder['order_no'],$receiver['user_id'],$notifyPrice));
            }
            return $payLog;
        }catch (\Exception $e){
            $connection->rollBack();
            Log::error("支付出错 ".$e->getMessage());
            throw new BusinessException($e->getMessage(),PayErrorCode::NOTIFY_FAILED);
        }

    }

    public function addPayMessage($payNo,$messageAction,$messageType,array $messageData){
        $payMessage = $this->getPayMessageModel();
        $payMessage->message_action = $messageAction;
        $payMessage->message_type = $messageType;
        $payMessage->msg = json_encode($messageData);
        $payMessage->pay_no = $payNo;
        $payMessage->save();
        return $payMessage->toArray();
    }

    public function getPayLog($payNo){
       return  ($this->getPayService())->getPayLog($payNo);
    }

    public function getPayLogByBizNo($bizNo, $getRefund = true, $getUser = true)
    {
        return  ($this->getPayService())->getPayLogByBizNo($bizNo, $getRefund, $getUser);
    }

    protected function paySuccess(Model $payLog,$outTradeNo){
        $payLog->pay_state = PayConst::STATE_PAY;
        $payLog->channel_trade_no = $outTradeNo;
        $payLog->save();
        PriceChange::where(['water_no'=>$payLog['biz_sub_no']])->get()->each(function ($priceChangeModel){
            $priceChangeModel->op_state = OrderConst::PRICE_OP_STATE_PAY;
            $priceChangeModel->save();
        });
    }

    protected function addService(array $servicePrice,Model $taskOrder){
        unset($servicePrice['pay_price']);
        $serviceTypeTextList = array_flip(OrderConst::getServiceTypeList());
        collect($servicePrice)->each(function ($servicePrice, $textIdentify) use ($serviceTypeTextList,$taskOrder) {
            if (!isset($serviceTypeTextList[$textIdentify])) {
                return;
            }
            $serviceType = $serviceTypeTextList[$textIdentify];
            $service = Service::where(['order_no' => $taskOrder['order_no'], 'service_type' => $serviceType])->first();
            if (empty($service)) {
                $service = new Service();
                $service->order_no = $taskOrder['order_no'];
                $service->user_id = $taskOrder['user_id'];
            }

            $service->service_price = $servicePrice;
            $service->service_type = $serviceType;
            $service->pay_state = PayConst::STATE_PAY;
            $service->pay_time = Carbon::now();
            $service->save();
        });
        $detailOrderService = $this->getDetailService();
        $detailOrderService->deleteUnPayService($taskOrder['order_no']);
    }

    public function appendPayPrice(Model $taskOrder,$payPrice){
        if($payPrice > 0) {
            $taskOrder->increment('pay_price', $payPrice);
            $taskOrder->increment('origin_price',$payPrice);
        }
    }
}
