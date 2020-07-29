<?php


namespace App\Services\Trade\Refund;


use App\Consts\ErrorCode\RefundErrorCode;
use App\Consts\Trade\OrderConst;
use App\Consts\Trade\PayConst;
use App\Consts\Trade\RefundConst;
use App\Exceptions\BusinessException;
use App\Services\ScService;
use App\Services\Trade\Traits\ModelTrait;
use App\Services\Trade\Traits\ServiceTrait;
use App\Utils\UniqueNo;
use Carbon\Carbon;
use Yansongda\LaravelPay\Facades\Pay;
use Yansongda\Pay\Exceptions\GatewayException;
use Yansongda\Pay\Exceptions\InvalidArgumentException;
use Yansongda\Pay\Exceptions\InvalidConfigException;
use Yansongda\Pay\Exceptions\InvalidSignException;
use Yansongda\Pay\Gateways\Alipay;
use Yansongda\Pay\Gateways\Wechat;

/**
 * 任务退款服务层
 *
 * Class RefundTaskOrderService
 * @package App\Services\Trade\Refund
 *
 */
class RefundTaskOrderService extends ScService
{
    use ServiceTrait;
    use ModelTrait;

    /**
     *
     *
     *
     * @param $orderNo
     * @param $refundPrice
     * @param int $inoutRefundType
     * @param array $refundServices
     * @param array $refundFeeType
     * @param bool $isCancelServices
     * @throws BusinessException
     * @throws GatewayException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     * @paran int $inoutRefundType
     */
    public function refundByEmployer($orderNo, $refundPrice,$inoutRefundType = PayConst::INOUT_TASK_REFUND, array $refundServices = [], array $refundFeeType = [],bool $isCancelServices = false){
        $taskOrder = $this->getTaskOrderModel()->getByOrderNo($orderNo);
        if($taskOrder['order_state'] == OrderConst::EMPLOYER_STATE_UN_START){
            throw new BusinessException("任务单还未付款",RefundErrorCode::CHECK_TASK_UN_PAY);
        }
        if($refundPrice <= 0){
            throw new BusinessException("退款金额错误",RefundErrorCode::CHECK_REFUND_PRICE_ERROR);
        }
        $employerUserId = $taskOrder['user_id'];
        $payService = $this->getPayService();
        $refundablePrice = $this->getEmployerRefundablePrice($orderNo,$refundServices,$refundFeeType);
        $payTotal = $refundablePrice['pay_total'];
        $inoutLogService = $this->getInoutLogService();
        if($payTotal <= 0){
            throw new BusinessException("支付金额错误，不能退款",RefundErrorCode::CHECK_PAY_PRICE_ERROR);
        }
        if($refundPrice > $payTotal){
            throw new BusinessException("退款金额不能大于支付总金额",RefundErrorCode::CHECK_REFUND_GREATER_PAY);
        }
        if($refundPrice > $refundablePrice['refundable_money']){
            throw new BusinessException("退款金额不能大于任务可退金额",RefundErrorCode::CHECK_REFUND_GREATER_REAL_REFUND);
        }
        //依次退款
        $employerAllPayList = $payService->getAllTaskOrderPayLog($orderNo,$employerUserId);//所有支付记录之和一定要等于pay_total
        $payLogTotal = $employerAllPayList->pluck('pay_price')->sum();
        if($payTotal != $payLogTotal){
            \Log::error("退款价格异常",[$payLogTotal,$payTotal,$refundPrice,$orderNo]);
            if($payTotal > $payLogTotal){
                throw new BusinessException("价格异常",RefundErrorCode::CHECK_PAY_PRICE_ERROR);
            }
        }
        foreach ($employerAllPayList as $employerPay){
            $payPrice = $employerPay['pay_price'];
            if($payPrice <= 0){
                continue;
            }
            $scale = bcdiv($payPrice,$payTotal,3);
            $singleRefund = bcmul($refundPrice,$scale);//小于1分钱的不退
            if($singleRefund <= 0) {
                return ;
            }
            $refundData = $this->refundByPayNo($employerPay['pay_no'], $singleRefund, $refundFeeType,$inoutRefundType);
            $inoutLogService->addInoutLog($employerPay['user_id'], $singleRefund, $employerPay['channel'], $inoutRefundType, $employerPay['biz_source'], $employerPay['biz_no'], $employerPay['user_id'], $refundData['refund_no']);
        }
        if($refundServices && $isCancelServices) {
            $serviceModel = $this->getServiceModel();
            $serviceModel->where(['order_no'=>$orderNo])->whereIn('service_type',$refundServices)->delete();
        }
    }

    public function getEmployerRefundablePrice($orderNo,array $refundService = [],array $refundFeeType = [])
    {
        $feeService = $this->getFeeTaskService();
        $detailService = $this->getDetailService();
        $refundServiceObj = $this->getRefundService();
        $taskOrder = $this->getTaskOrderModel()->getByOrderNo($orderNo);
        if($taskOrder['order_state'] <= OrderConst::EMPLOYER_STATE_UN_START){
            return [
                'helper_income' => 0,
                'refundable_money' => 0,
                'fee_total_money' => 0,
                'service_refund_money' => 0,
                'fee_refund_money' => 0,
                'service_total' => 0,
                'pay_total' => 0,
                'refund_money' => 0
            ];
        }
        $serviceRefundMoney = 0;
        $refundFeeTypeMoney = 0;
        $feeTotal = $feeService->sumUseByOrderNoAndFeeType($taskOrder['user_id'],$orderNo);
        if ($refundService) {
            $serviceRefundMoney = $detailService->sumEnableServiceByOrderNo($orderNo, $refundService);
        }
        if($refundFeeType){
            $refundFeeTypeMoney = $feeService->sumUseByOrderNoAndFeeType($taskOrder['user_id'],$orderNo,$refundFeeType);
        }
        $refundTotal = $refundServiceObj->sumRefundByOrderNo($orderNo,$taskOrder['user_id']);

        $serviceTotal = array_sum($detailService->getEnableServiceByOrderNo($orderNo));
        $payTotal = bcadd($taskOrder['pay_price'],$serviceTotal);
        $originPayTotal =  bcadd($taskOrder['origin_price'],$serviceTotal);

        $allRefundMoney = bcadd($serviceRefundMoney,$refundFeeTypeMoney);
        $allPlatformIncome = bcadd($serviceTotal,$feeTotal);

        $refundableMoney = bcsub($payTotal,$allPlatformIncome);
        $refundableMoney = bcsub($refundableMoney,$refundTotal);
        $refundableMoney = bcadd($refundableMoney,$allRefundMoney);

        $helperIncome = bcsub($originPayTotal,$allPlatformIncome);
        return [
            'helper_income' => $helperIncome,
            'refundable_money' => $refundableMoney,//可退总金额
            'fee_total_money' => $feeTotal,//已结算手续费总和
            'service_refund_money' => $serviceRefundMoney,//需要退款的服务价格总和
            'fee_refund_money' => $refundFeeTypeMoney,//有要退困的手续费总和
            'service_total' => $serviceTotal,//服务支付价格总和
            'pay_total' => $payTotal, //任务支付总价
            'refund_money'=>$refundTotal
        ];
    }


    public function getRefundableCompensatePrice($orderNo,$userId,$compensateType = PayConst::INOUT_OVERTIME_COMPENSATE,array $refundFeeType = [])
    {
        $compensateService = $this->getCompensateService();
        $helperCompensate = $compensateService->getUserCompensateByUserId($orderNo,$userId,$compensateType);
        $refundService = $this->getRefundService();

        if(!$helperCompensate){
            return [];
        }
        $feeService = $this->getFeeTaskService();
        $refundTotal = $refundService->sumRefundByOrderNo($orderNo,$userId);
        $refundFeeTypeMoney = 0;
        $feeTotal = $feeService->sumUseByOrderNoAndFeeType($userId,$orderNo);
        $payTotal = $helperCompensate['compensate_price'];
        if($refundFeeType){
            $refundFeeTypeMoney = $feeService->sumUseByOrderNoAndFeeType($userId,$orderNo,$refundFeeType);
        }
        $refundableMoney = bcsub($payTotal,$feeTotal);
        $refundableMoney = bcsub($refundableMoney,$refundTotal);
        $refundableMoney = bcadd($refundableMoney,$refundFeeTypeMoney);
        return [
            'refundable_money' => $refundableMoney,//可退总金额
            'fee_total_money' => $feeTotal,//已结算手续费总和
            'fee_refund_money' => $refundFeeTypeMoney,//有要退困的手续费总和
            'pay_total' => $payTotal,
            'pay_no' => $helperCompensate['pay_no'],
            'settled_at' => $helperCompensate['settled_at'],
            'compensate_id' =>$helperCompensate['compensate_id'],
            'refund_money' => $refundTotal
        ];
    }
    /**
     * 退款核心方法
     *
     * @param int $payNo
     * @param int $refundPrice
     * @param array $refundFeeType 可退平台收费类型
     * @param int $refundType
     * @return array
     * @throws BusinessException
     * @throws GatewayException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     */
    public function refundByPayNo($payNo,$refundPrice,array $refundFeeType = [],$refundType = PayConst::INOUT_TASK_REFUND){
        $payService = $this->getPayService();
        $refundService = $this->getRefundService();
        $feeService = $this->getFeeTaskService();
        $payLog = $payService->getPayLog($payNo);
        if(empty($payLog)){
            throw new BusinessException("支付凭据不存在",RefundErrorCode::CHECK_PAY_RECORD_NOT_EXIST);
        }
        if($payLog['pay_state'] != PayConst::STATE_PAY){
            throw new BusinessException("支付凭据还未支付",RefundErrorCode::CHECK_TASK_UN_PAY);
        }
        if($refundPrice > $payLog['pay_price']){
            throw new BusinessException("退款金额大于支付金额",RefundErrorCode::CHECK_REFUND_GREATER_PAY);
        }
        $orderNo = $payLog['biz_no'];
        $channel = $payLog['channel'];
        $channels = PayConst::getChannelList();
        if(!in_array($channel,array_keys($channels))){
            throw new BusinessException("退款渠道不支持",RefundErrorCode::CHECK_REFUND_CHANNEL_SUPPORT);
        }
        $info = UniqueNo::getInfoByNo($payLog['pay_no']);
        $refundNo = UniqueNo::buildRefundNo($payLog['user_id'],$info['business_sub_type']);
        $refundState = 0;
        $out_refund_id = '';
        $refundFee = [];
        if($channel == PayConst::CHANNEL_WECHAT){
            /** @var Wechat $wechat */
            $wechat = Pay::wechat();
            $order = [
                'transaction_id' => $payLog['channel_trade_no'],
                'out_trade_no' => $payNo, // 之前的订单流水号
                'total_fee' => $payLog['pay_price'], //原订单金额，单位分
                'refund_fee' => $refundPrice, //要退款的订单金额，单位分
                'out_refund_no' =>$refundNo, // 退款订单号
                'notify_url' => '',
                'refund_desc' => ''

            ];
            $response = $wechat->refund($order);
            $out_refund_id = $response['refund_id'] ?? '';
            $this->getPayTaskOrderService()->addPayMessage($payNo,PayConst::PAY_MESSAGE_ACTION_REFUND,PayConst::PAY_MESSAGE_TYPE_REQUEST,$response->toArray());
        }elseif($channel == PayConst::CHANNEL_ALIPAY){
            /** @var Alipay $alipay */
            $alipay = Pay::alipay();
            $order = [
                'trade_no' => $payLog['channel_trade_no'],
                'out_trade_no' => $payNo, // 之前的订单流水号
                'total_fee' => display_price($payLog['pay_price']), //原订单金额，单位元
                'refund_amount' => display_price($refundPrice), //要退款的订单金额，单位元
                'out_request_no' =>$refundNo, // 退款订单号
                'refund_reason' => ''
            ];
            //refund_settlement_id
            $response = $alipay->refund($order);
            $out_refund_id = $response['trade_no'] ?? '';
            $refundState = 1;
            $this->getPayTaskOrderService()->addPayMessage($payNo,PayConst::PAY_MESSAGE_ACTION_REFUND,PayConst::PAY_MESSAGE_TYPE_RESPONSE,$response->toArray());

        }elseif($channel == PayConst::CHANNEL_BALANCE){
            $out_refund_id = $refundNo;
            $refundState = 1;
            $balancePayment = $this->getBalancePayment();
            $balancePayment->add($payLog['user_id'],$refundPrice);
        }

        $refundData =[
            'user_id'=>$payLog['user_id'],
            'biz_no' => $orderNo,
            'biz_sub_no' => $payLog['biz_sub_no'],
            'biz_source'=> $payLog['biz_source'],
            'pay_no' => $payLog['pay_no'],
            'refund_no'=>$refundNo,
            'channel_refund_no' => $out_refund_id,
            'channel' => $payLog['channel'],
            'state' => $refundState,
            'refund_price' => $refundPrice,
            'refund_type' => $refundType
        ];
        $scale = bcdiv($refundPrice,$payLog['pay_price'],3);
        $refundService->addRefund($refundData,$refundState);
        foreach ($refundFeeType as $feeType){
            $feeList = $feeService->getUserByPayNoAndFeeType($payLog['user_id'],$payLog['pay_no'],[$feeType]);
            $feeList->each(function ($fee) use($scale,$refundData,&$refundFee){
                $refundFeePrice = bcmul($fee['fee_money'],$scale);//小于1分钱不退
                if($refundFeePrice <= 0){
                    return;
                }
                $refundFee[] = $this->formatFee($refundData,$fee->toArray(),$refundFeePrice);
            });
        }
        if($refundFee){
            $this->getRefundFeeModel()->insert($refundFee);
        }
        return $refundData;
    }

    /**
     * 按订单号单号给赔偿退款
     *
     * @param $orderNo
     * @param $helpUserId
     * @param int $compensateType
     * @throws BusinessException
     * @throws GatewayException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     */
    public function refundCompensateByOrderNo($orderNo,$helpUserId,$compensateType){
        $refundableHelper = $this->getRefundableCompensatePrice($orderNo,$helpUserId,$compensateType);
        $compensateMoney = $refundableHelper['refundable_money'] ?? 0;
        if($compensateMoney > 0  && !$refundableHelper['settled_at']){
            $this->refundCompensate($refundableHelper['pay_no'],$refundableHelper['refundable_money'],$compensateType);
        }
    }

    /**
     * 按支付单号给赔偿退款
     *
     * @param $payNo
     * @param $refundPrice
     * @param $compensateType
     * @param array $refundFeeType
     * @param int $inoutRefundType
     * @throws BusinessException
     * @throws GatewayException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws InvalidSignException
     * @return array
     */
    public function refundCompensate($payNo,$refundPrice,$compensateType,array $refundFeeType = [],$inoutRefundType = 0){
        $payService = $this->getPayService();
        $payLog = $payService->getPayLog($payNo);
        $inoutLogService = $this->getInoutLogService();
        if(empty($payLog)){
            throw new BusinessException("支付凭据不存在",RefundErrorCode::CHECK_PAY_RECORD_NOT_EXIST);
        }
        $compensate = $this->getRefundableCompensatePrice($payLog['biz_no'],$payLog['user_id'],$compensateType);
        if(empty($compensate)){
            throw new BusinessException("赔偿凭证不存在",RefundErrorCode::CHECK_COMPENSATE_RECORD_NOT_EXIST);
        }
        if($refundPrice > $compensate['refundable_money']){
            throw new BusinessException("退款金额不能大于赔偿金额",RefundErrorCode::CHECK_REFUND_GREATER_REAL_REFUND);
        }
        $refundData = $this->refundByPayNo($payNo,$refundPrice,$refundFeeType,$inoutRefundType);
        $compensateModel = $this->getCompensateModel()->find($compensate['compensate_id']);
        $compensateModel->refund_state = RefundConst::STATE_ALL;
        $compensateModel->refund_at = Carbon::now();
        $compensateModel->save();
        $inoutLogService->addInoutLog($payLog['user_id'],$refundPrice,$payLog['channel'],PayConst::INOUT_EMPLOYER_COMPENSATE_REFUND,$payLog['biz_source'],$payLog['biz_no'],$payLog['user_id'],$refundData['refund_no']);
        return $refundData;
    }

    /**
     * @param array $refund
     * @param array $fee
     * @param $refundFee
     * @return array
     * @throws BusinessException
     */
    protected function formatFee(array $refund,array $fee,$refundFee){
        $array = [
            'water_no' => UniqueNo::buildFeeRefundNo($refund['user_id']),
            'user_id' => $refund['user_id'],
            'fee_no' => $fee['water_no'],
            'biz_source' => $fee['biz_source'],
            'biz_no' => $fee['biz_no'],
            'refund_no' => $refund['refund_no'],
            'refund_money' => $refund['refund_price'],
            'fee_money_deduct' => $refundFee,
            'fee_type' => $fee['fee_type'],
            'state' => $refund['state']
        ];
        if($refund['state']){
            $array['settled_at'] = Carbon::now();
        }
        return $array;
    }

}
