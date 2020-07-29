<?php


namespace App\Consts\Trade;


class FeeConst
{
    const TYPE_TRADE = 1;
    const TYPE_SERVICE = 2;
    const TYPE_WITHDRAW = 3;
    const TYPE_WITHDRAW_BANK = 4;

    const STATE_DISABLE = 0;
    const STATE_ENABLE = 1;


    public static function getTypeList($type = null){
        $list = [
            self::TYPE_TRADE => '交易手续费',
            self::TYPE_SERVICE => '平台服务费',
            self::TYPE_WITHDRAW => '提现服务费',
            self::TYPE_WITHDRAW_BANK => '提现服务费'
        ];
        return is_null($type) ? $list : $list[$type] ?? '';
    }

    public static function getStateList($type = null) {
        $list = [
            self::STATE_DISABLE => '禁用',
            self::STATE_ENABLE => '启用',
        ];
        return is_null($type) ? $list : $list[$type] ?? '';
    }
}
