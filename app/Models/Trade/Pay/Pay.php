<?php


namespace App\Models\Trade\Pay;


use App\Consts\Trade\PayConst;
use App\Models\Trade\BaseTrade;

class Pay extends BaseTrade
{
    protected $table = 'pay';

    protected $primaryKey = 'pay_id';

    protected $casts = [
        'pay_no' => 'string',
        'biz_no' => 'string',
        'biz_sub_no' => 'string'
    ];
    public function getPayLogByPayNo($payNo){
        return   Pay::where('pay_no',$payNo)->first();
    }

    public function getPayLogBySubBizNo($subBizNo){
        return   Pay::where('biz_sub_no',$subBizNo)->first();
    }

    public function getAllPayLogByBizNo($bizNo, $userId = null){
        return Pay::where('biz_no', $bizNo)->when($userId, function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->where('pay_state', PayConst::STATE_PAY)->orderByDesc('pay_price')->get();
    }

    public function getPayLogByPayNos(array $payNos){
        return   Pay::whereIn('pay_no',$payNos)->get()->keyBy('pay_no');
    }

    public function getPayLogByBizNosAndState(array $payNos,int $payState = PayConst::STATE_PAY){
        if(empty($payNos)){
            return [];
        }
        return   Pay::whereIn('pay_no',$payNos)->where('pay_state',$payState)->get()->keyBy('pay_no');
    }
}
