<?php


namespace App\Services\Trade\Fee;


use App\Consts\ErrorCode\FeeErrorCode;
use App\Consts\GlobalConst;
use App\Consts\Trade\FeeConst;
use App\Consts\Trade\OrderConst;
use App\Consts\Trade\PayConst;
use App\Consts\UserConst;
use App\Exceptions\BusinessException;
use App\Models\Trade\Fee\FeeRule;
use App\Services\ScService;
use App\Services\Trade\Traits\ModelTrait;
use App\Services\Trade\Traits\ServiceTrait;
use App\Utils\UniqueNo;
use Carbon\Carbon;
use Cassandra\Type\UserType;
use Illuminate\Support\Facades\Validator;

/**
 * 收续费与服务费，即第三方交易费用与平台抽成费用
 *
 * Class FeeTaskService
 * @package App\Services\Trade\Fee
 */
class FeeTaskService extends ScService
{
    use ServiceTrait;
    use ModelTrait;

    const TEN_THOUSAND_CENT = 10000;


    public function getTaskServiceRule($channel){
        return $this->getTaskOrderFeeRule($channel,FeeConst::TYPE_SERVICE);
    }

    public function getTaskTradeRule($channel){
        return $this->getTaskOrderFeeRule($channel,FeeConst::TYPE_TRADE);
    }

    public function getTaskOrderFeeRule($channel,$feeType){
        $rule = $this->getFeeRuleModel()->getTaskOrderFeeRule($channel,$feeType);
        return $rule ? $rule->toArray() : [];
    }

    /**
     * 预估最高手续费，那个支付渠道最高就用那个
     *
     * @param $price
     * @return array
     */
    public function computePriceFee($price){
        $db_price = db_price($price);
        $return['trade_fee'] = 0;
        $return['service_fee'] = 0;
        if($price <= 0){
            return $return;
        }
        $serviceRule = $this->getTaskServiceRule(PayConst::CHANNEL_WECHAT);
        $tradeRule = $this->getTaskTradeRule(PayConst::CHANNEL_WECHAT);
        $return['trade_fee'] = display_price($this->calcFee($db_price, $tradeRule['ratio']));
        $return['service_fee'] =  display_price($this->calcFee($db_price, $serviceRule['ratio']));
        return $return;
    }
    /**
     * 每笔交易收取的交易手续费和平台服务费
     *
     * @param int $orderNo
     * @param int $taskUserId
     * @return array
     */
    public function computeTaskFeeByOrderNo($orderNo,$taskUserId){
        $payLogList = $this->getPayService()->getAllTaskOrderPayLog($orderNo,$taskUserId);
        $return['trade_fee']['price'] = 0;
        $return['service_fee']['price'] = 0;
        foreach($payLogList as $payLog){

            if($payLog['third_fee'] > 0){
                $return['trade_fee']['price'] += $payLog['third_fee'];
            }
            if($payLog['platform_fee'] > 0){
                $return['service_fee']['price'] += $payLog['platform_fee'];
            }
        }
        $return['trade_fee']['display_price'] = display_price($return['trade_fee']['price']);
        $return['service_fee']['display_price'] =  display_price($return['service_fee']['price']);
        return $return;
    }

    /**
     * 每笔交易收取的交易手续费和平台服务费
     *
     * @param $payNo
     * @param boolean $isSettled 是否立即结算
     * @param boolean $isCompensate 是否赔偿
     * @return array
     * @throws BusinessException
     */
    public function computeTaskFee($payNo,$isSettled = false,$isCompensate = false){
        $payLog = $this->getPayService()->getPayLog($payNo);
        $channel = $payLog['channel'];
        $tradeRule = $this->getTaskTradeRule($channel);
        $serviceRule = $this->getTaskServiceRule($channel);
        $feeLog = [];
        $list = [];
        if($serviceRule){
            $list[]  = $serviceRule;
        }
        if($tradeRule){
            $list[]  = $tradeRule;
        }
        foreach($list as $rule){
            //交易手续费按支付价格计算，其它则按任务原始价计算
            if($rule['fee_type'] == FeeConst::TYPE_TRADE || $isCompensate) {
                $feeMoney = $this->calcFee($payLog['pay_price'], $rule['ratio']);
            }else{
                $task = $this->getTaskOrderModel()->getByOrderNo($payLog['biz_no']);
                $feeMoney = $this->calcFee($task['origin_price'], $rule['ratio']);
            }
            if($feeMoney > 0) {
                $feeLog[] = $this->formatFeeLog($payLog, $rule, $feeMoney,$isSettled);
            }
        }
        $feeLogModel = $this->getFeeLogModel();
        if($feeLog){
            $feeLogModel->insert($feeLog);
        }
        return collect($feeLog)->pluck('fee_money','fee_type')->toArray();
    }



    /**
     * 帮手取消订单赔偿金额分摊
     *
     * @param $payNo
     * @return array
     * @throws BusinessException
     */
    public function computeHelpCancel($payNo){
        $payLog = $this->getPayService()->getPayLog($payNo);
        $platServiceLog = $this->computeTaskFee($payNo,true,true);
        $platServiceTotal = array_sum($platServiceLog);
        $taskOrderModel = $this->getTaskOrderModel();
        $order = $taskOrderModel->getByOrderNo($payLog['biz_no']);
        $employerFee = bcsub($payLog['pay_price'],$platServiceTotal);
        if($employerFee > 0) {
            $compensateService = $this->getCompensateService();
            $compensateService->compensate($payLog['user_id'],$order['user_id'],$payLog['pay_price'],$employerFee,PayConst::INOUT_HELPER_CANCEL_COMPENSATE_COMPLETE,$payNo,$payLog['biz_no']);
            $compensateService->settled($payLog['biz_no'],PayConst::INOUT_HELPER_CANCEL_COMPENSATE_COMPLETE);
        }
        return $platServiceLog;
    }

    /**
     * 雇主取消订单，雇主一般会修改订单，多次支付，无法确定支付号
     *
     * @param $orderNo
     * @param int  $payPrice  任务可退的钱
     * @return array
     * @throws BusinessException
     */
    public function computeEmployerCancel($orderNo,$payPrice){
        $taskOrderModel = $this->getTaskOrderModel();
        $taskOrder = $taskOrderModel->getByOrderNo($orderNo);
        $payLog = $this->getPayService()->getAllTaskOrderPayLog($orderNo,$taskOrder['user_id']);//取支付最高一笔钱的支付单号做记录
        $payLog = $payLog[0];
        $helper_price = cancel_compensate_price($taskOrder['order_state'],$payPrice,UserConst::TYPE_EMPLOYER,$taskOrder['start_time'],$taskOrder['end_time']);//雇主取消按可退钱算
        if($helper_price > 0 && $taskOrder['helper_user_id']) {
            $compensateService = $this->getCompensateService();
            $inoutLogService = $this->getInoutLogService();
            $compensateService->compensate($taskOrder['user_id'],$taskOrder['helper_user_id'],$payPrice,$helper_price,PayConst::INOUT_EMPLOYER_COMPENSATE_COMPLETE,$payLog['pay_no'],$orderNo);
            $compensateService->settled($payLog['biz_no'],PayConst::INOUT_EMPLOYER_COMPENSATE_COMPLETE);
            $inoutLogService->addInoutLog($taskOrder['user_id'], convert_negative_number($helper_price), $payLog['channel'], PayConst::INOUT_EMPLOYER_COMPENSATE, $payLog['biz_source'], $payLog['biz_no'], $taskOrder['helper_user_id']);

        }
        return $helper_price;
    }



    public function calcFee($payPrice,$ratio){
        $payPrice = display_price($payPrice);
        $platformFee = bcdiv(bcmul($payPrice,$ratio),self::TEN_THOUSAND_CENT,3);
        $platformFee = round($platformFee,2);
        return db_price($platformFee);
    }

    public function orderCompleteSettled($orderNo){
        $this->getFeeLogModel()->feeSettled($orderNo);
        return true;
    }


    public function getUserByOrderNoAndFeeType($userId,$orderNo, array $feeType = [],$isSettled = false){
        return $this->getFeeLogModel()->getByBizNoAndFeeType($userId,$orderNo,$feeType,$isSettled);
    }

    public function getUserByPayNoAndFeeType($userId,$payNo, array $feeType = [],$isSettled = false){
        return $this->getFeeLogModel()->getByPayNoAndFeeType($userId,$payNo,$feeType,$isSettled);
    }


    public function sumUseByOrderNoAndFeeType($userId, $orderNo, array $feeType = [],$isSettled = false){
        return $this->getFeeLogModel()->sumByBizNoAndFeeType($userId,$orderNo,$feeType,$isSettled);
    }

    /**
     * @param array $feeLog
     * @return array
     * @throws BusinessException
     */
    public function addFeeLog(array $feeLog){
        $feeLogModel = $this->getFeeLogModel();
        $validate = Validator::make($feeLog,[
            'user_id'=>'required|integer',
            'water_no'=>'required|integer',
            'pay_no'=>'required|integer',
            'biz_no'=>'required|integer',
            'money'=>'required|integer',
            'fee_type'=>'required|integer',
            'fee_money'=>'required|integer',
            'rule_id'=>'required|integer'
        ],[
            'user_id.required' => '用户标识不能为空',
            'water_no.required' => '流水号不能为空',
            'pay_no.required' => '支付单号不能为空',
            'biz_no.required'=>"业务单号不能为空",
            'money.required'=>"计费金额不能为空",
            'fee_type.required'=>'计费类型不能为空',
            'fee_money.required' => "费用金额不能为空",
            'rule_id.required' => "计费规则ID不能为空"
        ]);
        if($validate->fails()){
            throw new BusinessException($validate->errors()->first(),FeeErrorCode::CHECK_VALIDATION_ERROR);
        }
        $fields = $feeLogModel->getTableColumns();
        foreach ($fields as $field) {
            if ($field == $feeLogModel->getKeyName()) {
                continue;
            }
            if (isset($feeLog[$field])) {
                $feeLogModel->$field = $feeLog[$field];
            }
        }
        $feeLogModel->save();
        return $feeLogModel->toArray();
    }


    /**
     * @param $payLog
     * @param $rule
     * @param $feeMoney
     * @param bool $isSettled 是否立即结算
     * @return array
     * @throws BusinessException
     */
    protected function formatFeeLog($payLog,$rule,$feeMoney,$isSettled = false){
        $format = [];
        $userId = $payLog['user_id'];
        $format['user_id'] = $userId;
        $format['water_no'] = UniqueNo::buildPlatformFeeNo($userId,$rule['fee_type']);
        $format['pay_no'] = $payLog['pay_no'];
        $format['biz_no'] = $payLog['biz_no'];
        $format['biz_source'] = $payLog['biz_source'];
        $format['money'] = $payLog['pay_price'];
        $format['fee_money'] = $feeMoney;
        $format['fee_type'] = $rule['fee_type'];
        $format['rule_id'] = $rule['fee_rule_id'];
        $format['rule_ratio'] = $rule['ratio'];
        if($isSettled){
            $format['settled_at'] = Carbon::now();
        }
        return $format;
    }

}
