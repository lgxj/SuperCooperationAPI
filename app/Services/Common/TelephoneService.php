<?php


namespace App\Services\Common;


use App\Consts\ErrorCode\TelephoneErrorCode;
use App\Consts\TelephoneConst;
use App\Exceptions\BusinessException;
use App\Services\ScService;

class TelephoneService extends ScService
{
    public function createCallBack($userId,$orderId,$srcTel,$dstTel,$bizType = TelephoneConst::BIZ_TYPE_TASK,$isRecord = 0,$maxAllowTime = 10){
        if($userId <= 0 || $orderId <= 0){
            throw new BusinessException("参数错误",TelephoneErrorCode::PARAM_ERROR);
        }
        if(empty($srcTel) || empty($dstTel)){
            throw new BusinessException("主叫电码/被叫电话号码为空",TelephoneErrorCode::PARAM_ERROR);
        }

        if(!in_array($bizType,[TelephoneConst::BIZ_TYPE_TASK])){
            throw new BusinessException("业务类型错误",TelephoneErrorCode::PARAM_ERROR);
        }

    }
}
