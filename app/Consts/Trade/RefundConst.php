<?php


namespace App\Consts\Trade;


class RefundConst
{


    const STATE_NOT = 0;
    const STATE_ALL = 1;
    const STATE_PART = 2;

    public static function getRefundList($channel = null){
        $list = [
            self::STATE_NOT => '未退款',
            self::STATE_ALL => '全额退款',
            self::STATE_PART => '部分退款'
        ];
        return is_null($channel) ? $list : $list[$channel] ?? '';
    }

}
