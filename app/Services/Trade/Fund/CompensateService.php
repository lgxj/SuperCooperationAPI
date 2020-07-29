<?php


namespace App\Services\Trade\Fund;


use App\Consts\Trade\PayConst;
use App\Exceptions\BusinessException;
use App\Models\Trade\Order\Compensate;
use App\Services\ScService;
use App\Services\Trade\Traits\ModelTrait;
use App\Services\Trade\Traits\ServiceTrait;
use Carbon\Carbon;

/**
 * 赔偿退款服务
 * compensate_type同inout_type
 *
 * Class CompensateService
 * @package App\Services\Trade\Fund
 */
class CompensateService extends ScService
{
    use ServiceTrait;
    use ModelTrait;

    public function compensate($userId,$toUserId,$money,$price,$type,$payNo,$orderNo){
        if($userId <= 0 || $toUserId <= 0){
            throw new BusinessException("用户信息错误");
        }
        if($price <= 0){
            throw new BusinessException("价格错误");
        }
        if(empty($payNo) || empty($orderNo) ){
            throw new BusinessException("业务信息错误");
        }
        $compensateModel = $this->getCompensateModel();
        $compensateModel->user_id = $userId;
        $compensateModel->to_user_id = $toUserId;
        $compensateModel->compensate_type = $type;
        $compensateModel->compensate_price = $price;
        $compensateModel->pay_no = $payNo;
        $compensateModel->order_no = $orderNo;
        $compensateModel->money = $money;
        $compensateModel->save();
        return $compensateModel->toArray();
    }

    public function settled($orderNo,$type = PayConst::INOUT_HELPER_CANCEL_COMPENSATE){
       $compensates = Compensate::where(['order_no'=>$orderNo,'compensate_type'=>$type])->get();
       if(empty($compensates)){
           return [];
       }
        $inoutLogService = $this->getInoutLogService();
        $compensatePrice = [];
        $compensates->each(function ($compensate)use($inoutLogService,&$compensatePrice){
            if($compensate['settled_at']){
                return;
            }
            if($compensate['compensate_type'] != PayConst::INOUT_OVERTIME_COMPENSATE) {
                $balancePayment = $this->getBalancePayment();
                $payService = $this->getPayService();
                $payLog = $payService->getPayLog($compensate['pay_no']);
                $balancePayment->add($compensate['to_user_id'], $compensate['compensate_price']);
                $inoutLogService->addInoutLog($compensate['to_user_id'], $compensate['compensate_price'], $payLog['channel'], $compensate['compensate_type'], $payLog['biz_source'], $payLog['biz_no'], 0);
            }
            $compensate->settled_at = Carbon::now();
            $compensate->save();
            $compensatePrice[$compensate['compensate_type']] = $compensate['compensate_price'];
        });
        return $compensatePrice;
    }

    public function getUserCompensateByToUserId($orderNo,$toUserId,$type){
        if(empty($orderNo) || $toUserId <= 0){
            return null;
        }
        return $this->getCompensateModel()->getUserCompensateByToUserId($orderNo,$toUserId,$type);
    }

    public function getUserCompensateByUserId($orderNo,$userId,$type){
        if(empty($orderNo) || $userId <= 0){
            return null;
        }
        return $this->getCompensateModel()->getUserCompensateByUserId($orderNo,$userId,$type);
    }
}
