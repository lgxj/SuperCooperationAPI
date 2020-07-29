<?php


namespace App\Consts\Trade;

class PayConst
{

    const STATE_UN_PAY = 0;
    const STATE_PAY = 1;

    const SOURCE_TASK_ORDER = 1;
    const SOURCE_WITHDRAW = 2;
    const SOURCE_RECHARGE = 3;

    const ACCOUNT_CHANGE_ADMIN_ADD = 0;
    const ACCOUNT_CHANGE_ADMIN_FRAZE = 1;

    const CHANNEL_WECHAT = 'wechat';
    const CHANNEL_ALIPAY = 'alipay';
    const CHANNEL_BALANCE = 'balance';

    const CHANNEL_BALANCE_NORMAL = 0;
    const CHANNEL_WECHAT_APP = 1;
    const CHANNEL_WECHAT_WAP = 2;
    const CHANNEL_WECHAT_MP = 3;
    const CHANNEL_WECHAT_MINIAPP = 4;
    const CHANNEL_WECHAT_SCAN = 5;
    const CHANNEL_WECHAT_POS = 6;
    const CHANNEL_WECHAT_TRANSFER = 7;
    const CHANNEL_WECHAT_REDBACK = 8;
    const CHANNEL_WECHAT_GROUPREDBACK = 9;

    const CHANNEL_ALIPAY_WEB = 10;
    const CHANNEL_ALIPAY_APP = 11;
    const CHANNEL_ALIPAY_WAP = 12;
    const CHANNEL_ALIPAY_MINIAPP = 13;
    const CHANNEL_ALIPAY_SCAN = 14;
    const CHANNEL_ALIPAY_POS = 15;
    const CHANNEL_ALIPAY_TRANSFER = 16;

    const INOUT_PAY = 0;
    const INOUT_OVERTIME_COMPENSATE = 1;
    const INOUT_WITHDRAW = 2;
    const INOUT_TASK_REFUND = 3;
    const INOUT_RECHARGE = 4;
    const INOUT_HELPER_CANCEL_COMPENSATE = 5;
    const INOUT_EMPLOYER_COMPENSATE = 6;
    const INOUT_OVERTIME_EMPLOYER_COMPENSATE = 7;
    const INOUT_EMPLOYER_OVERPAY_REFUND = 8;
    const INOUT_HELPER_COMPLETE = 9;
    const INOUT_EMPLOYER_COMPENSATE_REFUND = 10;
    const INOUT_EMPLOYER_COMPENSATE_COMPLETE = 11;
    const INOUT_HELPER_CANCEL_COMPENSATE_COMPLETE = 12;
    const INOUT_BALANCE_ADMIN = 13;

    const IN_OUT_OUT = 0;
    const IN_OUT_IN = 1;

    const PAY_MESSAGE_ACTION_PAY = 1;
    const PAY_MESSAGE_ACTION_REFUND = 2;

    const PAY_MESSAGE_TYPE_REQUEST = 1;
    const PAY_MESSAGE_TYPE_RESPONSE = 2;
    const PAY_MESSAGE_TYPE_NOTIFY = 3;

    const REFUND_STATE_NO = 0;  // 退㰪状态：未处理
    const REFUND_STATE_YES = 1; // 退㰪状态：已处理

    public static function getStateList($state = null){
        $list = [
            self::STATE_UN_PAY => '未支付',
            self::STATE_PAY => '已支付'
        ];
        return is_null($state) ? $list : $list[$state] ?? '';
    }

    public static function getRefundStateList($state = null){
        $list = [
            self::REFUND_STATE_NO => '未处理',
            self::REFUND_STATE_YES => '已处理'
        ];
        return is_null($state) ? $list : $list[$state] ?? '';
    }

    public static function getChannelList($channel = null){
        $list = [
            self::CHANNEL_ALIPAY => '支付宝',
            self::CHANNEL_WECHAT => '微信',
            self::CHANNEL_BALANCE => '余额'
        ];
        return is_null($channel) ? $list : $list[$channel] ?? '';
    }

    public static function getAlipayPayTypeList($payType = null){
        $list = [
            self::CHANNEL_ALIPAY_WEB => 'web',
            self::CHANNEL_ALIPAY_APP => 'app',
            self::CHANNEL_ALIPAY_WAP => 'wap',
            self::CHANNEL_ALIPAY_MINIAPP => 'mini',
            self::CHANNEL_ALIPAY_SCAN => 'scan',
            self::CHANNEL_ALIPAY_POS => 'pos',
            self::CHANNEL_ALIPAY_TRANSFER => 'transfer'
        ];
        return is_null($payType) ? $list : $list[$payType] ?? '';
    }

    public static function getWechatPayTypeList($payType = null){
        $list = [
            self::CHANNEL_WECHAT_REDBACK => 'redback',
            self::CHANNEL_WECHAT_APP => 'app',
            self::CHANNEL_WECHAT_WAP => 'wap',
            self::CHANNEL_WECHAT_MINIAPP => 'miniapp',
            self::CHANNEL_WECHAT_SCAN => 'scan',
            self::CHANNEL_WECHAT_POS => 'pos',
            self::CHANNEL_WECHAT_TRANSFER => 'transfer',
            self::CHANNEL_WECHAT_GROUPREDBACK => 'groupRedpack',
            self::CHANNEL_WECHAT_MP => 'mp'
        ];
        return is_null($payType) ? $list : $list[$payType] ?? '';
    }

    public static function getPayTypeList($payType = null){
        $wechatList = self::getWechatPayTypeList();
        $alipayList = self::getAlipayPayTypeList();
        $list = $wechatList+$alipayList;
        $list[self::CHANNEL_BALANCE_NORMAL] = 'normal';
        return is_null($payType) ? $list : $list[$payType] ?? 0;
    }

    public static function getInoutTypeList($type = null){
        $list = [
            self::INOUT_PAY => '发布任务',
            self::INOUT_OVERTIME_COMPENSATE => '逾期交付抵扣',
            self::INOUT_RECHARGE => '充值',
            self::INOUT_TASK_REFUND => '任务取消',
            self::INOUT_WITHDRAW => '提现',
            self::INOUT_HELPER_CANCEL_COMPENSATE => '接单取消赔付',
            self::INOUT_EMPLOYER_COMPENSATE => '任务取消赔付',
            self::INOUT_OVERTIME_EMPLOYER_COMPENSATE => '逾期赔付到账',
            self::INOUT_EMPLOYER_OVERPAY_REFUND => '任务超付退款',
            self::INOUT_EMPLOYER_COMPENSATE_REFUND  => '赔付退款',
            self::INOUT_EMPLOYER_COMPENSATE_COMPLETE => '雇主赔付到账',
            self::INOUT_HELPER_CANCEL_COMPENSATE_COMPLETE => '帮手赔付到账',
            self::INOUT_HELPER_COMPLETE => '完成任务',
            self::INOUT_BALANCE_ADMIN => '平台转账',

        ];
        return is_null($type) ? $list : $list[$type] ?? 0;
    }

    public static function getInoutTypeLogo($type = null){
        $list = [
            self::INOUT_PAY => 'bangbang',
            self::INOUT_OVERTIME_COMPENSATE => 'compensation',
            self::INOUT_RECHARGE => 'recharge',
            self::INOUT_TASK_REFUND => 'bangbang',
            self::INOUT_WITHDRAW => 'withdraw',
            self::INOUT_HELPER_CANCEL_COMPENSATE => 'compensation',
            self::INOUT_EMPLOYER_COMPENSATE => 'compensation',
            self::INOUT_OVERTIME_EMPLOYER_COMPENSATE => 'compensation',
            self::INOUT_EMPLOYER_OVERPAY_REFUND => 'compensation',
            self::INOUT_EMPLOYER_COMPENSATE_REFUND => 'compensation',
            self::INOUT_EMPLOYER_COMPENSATE_COMPLETE => 'compensation',
            self::INOUT_HELPER_CANCEL_COMPENSATE_COMPLETE => 'compensation',
            self::INOUT_HELPER_COMPLETE => 'bangbang',
            self::INOUT_BALANCE_ADMIN => 'recharge'

        ];
        return is_null($type) ? $list : $list[$type] ?? 0;
    }

    public static function getBizSourceList($type = null){
        $list = [
            self::SOURCE_TASK_ORDER => '任务单',
            self::SOURCE_WITHDRAW => '提现',
            self::SOURCE_RECHARGE => '充值'
        ];
        return is_null($type) ? $list : $list[$type] ?? 0;
    }

    public static function getEmployerRefundType(){
        return [PayConst::INOUT_TASK_REFUND,PayConst::INOUT_EMPLOYER_OVERPAY_REFUND,PayConst::INOUT_OVERTIME_EMPLOYER_COMPENSATE];
    }
}
