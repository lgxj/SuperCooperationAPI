<?php


namespace App\Models\Trade\Fee;


use App\Consts\Trade\FeeConst;
use App\Consts\Trade\OrderConst;
use App\Consts\Trade\PayConst;
use App\Models\Trade\BaseTrade;

/**
 * 平台收费规则，同一种类型在同一支付下同时生效的只有一个
 *
 * Class FeeRule
 * @package App\Models\Trade\Fee
 */
class FeeRule extends BaseTrade
{
    protected $table = 'fund_fee_rule';

    protected $primaryKey = 'fee_rule_id';

    public function getTaskOrderFeeRule($channel,$type){
        return $this->where(['channel'=>$channel,'fee_type'=>$type,'state'=>FeeConst::STATE_ENABLE,'biz_source'=>PayConst::SOURCE_TASK_ORDER])->first();
    }
}
