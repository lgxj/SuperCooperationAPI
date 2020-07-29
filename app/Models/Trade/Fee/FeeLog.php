<?php


namespace App\Models\Trade\Fee;


use App\Consts\Trade\PayConst;
use App\Models\Trade\BaseTrade;
use Carbon\Carbon;

class FeeLog extends BaseTrade
{
    protected $table = 'fund_fee_log';

    protected $primaryKey = 'fee_log_id';

    protected $casts = [
        'water_no' => 'string',
        'biz_no' => 'string',
        'pay_no' => 'string'
    ];

    public function feeSettled($bizNo){
        return  $this->where(['biz_no'=>$bizNo])->whereNull('settled_at')->update(['settled_at'=>Carbon::now()]);
    }

    public function getByPayNoAndFeeType($userId, $payNo, array $feeType = [],$isSettled = false){
        if($userId <= 0 || empty($payNo)){
            return null;
        }
        $query = $this->where(['pay_no'=>$payNo,'user_id'=>$userId]);
        $query->when(!empty($feeType),function ($query) use ($feeType) {
            $query->whereIn('fee_type',$feeType);
        }
        );
        $query->when($isSettled,function ($query) use ($feeType) {
            $query->whereNotNull('settled_at');
        }
        );
        return $query->get();
    }

    public function getByBizNoAndFeeType($userId, $bizNo, array $feeType = [],$isSettled = false){
        if($userId <= 0 || empty($bizNo)){
            return null;
        }
        $query = $this->where(['biz_no'=>$bizNo,'user_id'=>$userId]);
        $query->when(!empty($feeType),function ($query) use ($feeType) {
            $query->whereIn('fee_type',$feeType);
        }
        );
        $query->when($isSettled,function ($query) use ($feeType) {
            $query->whereNotNull('settled_at');
        }
        );
        return $query->get();
    }

    public function sumByBizNoAndFeeType($userId, $bizNo, array $feeType = [],$isSettled = false){
        $query = $this->where(['biz_no'=>$bizNo,'user_id'=>$userId]);
        $query->when(!empty($feeType),function ($query) use ($feeType) {
            $query->whereIn('fee_type',$feeType);
            }
        );
        $query->when($isSettled,function ($query) use ($feeType) {
            $query->whereNotNull('settled_at');
        }
        );
        return $query->sum('fee_money');
    }

    public function getUserAllFeeByPayNo($payNo){
        return $this->where(['pay_no'=>$payNo])->whereNotNull('settled_at')->get();
    }
}
