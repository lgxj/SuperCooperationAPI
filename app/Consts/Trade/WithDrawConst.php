<?php


namespace App\Consts\Trade;


class WithDrawConst
{
    const TYPE_WEIXIN = 0;
    const TYPE_ALIPAY = 1;
    const TYPE_BANK = 2;

    const TRANSFER_TYPE_NONE = 0;
    const TRANSFER_TYPE_WEIXIN = 1;
    const TRANSFER_TYPE_ALIPAY = 2;

    const MIN_WITH_DRAW = 200;
    const WITH_DRAW_CHECK = 50000;

    const STATUS_UN_VERIFY = 0;
    const STATUS_VERIFY = 1;
    const STATUS_RETRY = 2;
    const STATUS_COMPLETE = 3;
    const STATUS_FAILED = 4;

    const FREEZE_TYPE_WITHDRAW = 1;
    const FREEZE_TYPE_TRANSFER = 2;
    const FREEZE_TYPE_DEPOSIT = 3;

    const FREEZE_STATUS_DOING = 1;
    const FREEZE_STATUS_REVERSE = 2;

    public static function getTypeList($type = null){
        $list = [
            self::TYPE_WEIXIN => '微信',
            self::TYPE_ALIPAY => '支付宝',
            self::TYPE_BANK => '银行卡',
        ];
        return is_null($type) ? $list : $list[$type] ?? '';
    }

    public static function getTransferTypeList($type = null){
        $list = [
            self::TRANSFER_TYPE_NONE => '暂无',
            self::TRANSFER_TYPE_WEIXIN => '微信',
            self::TRANSFER_TYPE_ALIPAY => '支付宝'
        ];
        return is_null($type) ? $list : $list[$type] ?? '';
    }

    public static function getStatusList($status = null){
        $list = [
            self::STATUS_UN_VERIFY => '待审核',
            self::STATUS_VERIFY => '已审核',
            self::STATUS_RETRY => '提现中',
            self::STATUS_COMPLETE => '提现成功',
            self::STATUS_FAILED => '提现失败'
        ];
        return is_null($status) ? $list : $list[$status] ?? '';

    }
}
