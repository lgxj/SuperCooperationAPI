<?php


namespace App\Consts\Trade;


class OrderConst
{

    /**
     * 订单类型
     */
    const TYPE_GENERAL = 0;

    const TYPE_COMPETITION  = 1;

    const TYPE_COMPETITION_LOW_PRICE  = 10;//竞价订单最低低价

    const TYPE_GENERAL_LOW_PRICE = 5;//普通订单最低支付价

    /**
     * 任务单状态
     */
    const EMPLOYER_STATE_UN_START = 0;

    const EMPLOYER_STATE_UN_RECEIVE = 1;

    const EMPLOYER_STATE_UN_CONFIRM = 2;

    const EMPLOYER_STATE_RECEIVE = 3;

    const EMPLOYER_STATE_DELIVERED  = 4;

    const EMPLOYER_STATE_CANCEL  = 5;

    const EMPLOYER_STATE_COMPLETE  = 6;

    const EMPLOYER_STATE_REFUSE_DELIVERY  = 7;

    /**
     * 帮手接单状态
     */

    const HELPER_STATE_RECEIVE = 1;

    const HELPER_STATE_CANCEL = 2;

    const HELPER_STATE_DELIVERED = 3;

    const HELPER_STATE_COMPLETE = 4;

    const HELPER_STATE_EMPLOYER_UN_CONFIRM = 5;

    const HELPER_STATE_REFUSE_DELIVERY  = 6;

    /**
     * 订单服务类型价格,不能跟price_change的value重复
     * PRICE_CHANGE_ORDER_PAY接着是从 4开始的
     */

    const SERVICE_PRICE_TYPE_URGE = 1;

    const SERVICE_PRICE_TYPE_INSURANCE = 2;

    const SERVICE_PRICE_TYPE_FACE  = 3;


    /**
     * 价格变动类型，附待服务价格
     */
    const PRICE_CHANGE_ORDER_PAY = 4; // 首次支付

    const PRICE_CHANGE_MAKE_UP = 5; // 改价

    const PRICE_CHANGE_CONFIRM = 6; // 确认付款

    const PRICE_CHANGE_HELPER_OVERTIME = 7; //帮手超时赔款

    const PRICE_CHANGE_HELPER_CANCEL = 8; //帮手取消赔付

    const PRICE_CHANGE_EMPLOYER_CANCEL = 9; //雇主取消赔付


    const PRICE_OP_STATE_UN_HANDLE = 0;
    const PRICE_OP_STATE_PAY = 1;

    const INOUT_OUT = 0;
    const INOUT_IN = 1;

    const ATTACHMENT_TYPE_FILE = 1;
    const ATTACHMENT_TYPE_VIDEO = 2;

    const TIME_EXPIRED = 2;
    const TIME_UN_START =0;
    const TIME_DOING = 1;

    const CANCEL_TYPE_EMPLOYER_COMPETITION_FAIL = 1; //竞价失败取消
    const CANCEL_TYPE_EMPLOYER_LEAVE = 2;
    const CANCEL_TYPE_EMPLOYER_SOLUTION = 3;
    const CANCEL_TYPE_EMPLOYER_OTHER = 4;
    const CANCEL_TYPE_EMPLOYER_SYSTEM = 5;

    const CANCEL_TYPE_HELPER_LEAVE = 5;
    const CANCEL_TYPE_HELPER_BAD_WEATHER = 6;
    const CANCEL_TYPE_HELPER_CONTRACT = 7;
    const CANCEL_TYPE_HELPER_MODIFY = 8;
    const CANCEL_TYPE_HELPER_OTHER = 9;
    const CANCEL_TYPE_HELPER_QUOTED = 10;

    const REFUSE_TYPE_EMPLOYER_LEAVE = 1;
    const REFUSE_TYPE_EMPLOYER_UN_STANDARDS = 2;
    const REFUSE_TYPE_EMPLOYER_OTHERS = 3;

    const CANCEL_COMPENSATE_STATUS_UNKNOWN = 0;
    const CANCEL_COMPENSATE_STATUS_HAS = 1;
    const CANCEL_COMPENSATE_STATUS_COMPLETE = 2;

    const HELPER_MAX_CANCEL_TODAY = 3;//帮手一天最大取消次数
    const HELPER_MAX_CANCEL_WEEK = 10;//帮手一周最大取消次数，超过这个次数，拉进黑名单

    const HELPER_REFER_STATUS_APPLY = 0;    // 帮手申请延迟交付：申请中
    const HELPER_REFER_STATUS_AGREE = 1;    // 雇主同意
    const HELPER_REFER_STATUS_REFUSE = 2;   // 雇主拒绝

    public static function getHelperReferStatus($status = null)
    {
        $list = [
            self::HELPER_REFER_STATUS_APPLY => '申请中',
            self::HELPER_REFER_STATUS_AGREE => '雇主同意',
            self::HELPER_REFER_STATUS_REFUSE => '雇主拒绝'
        ];
        return is_null($status) ? $list : $list[$status] ?? '';
    }

    public static function getTypeList($type = null){
        $list = [
            self::TYPE_GENERAL => '悬赏任务',
            self::TYPE_COMPETITION => '竞价任务'
        ];
        return is_null($type) ? $list : $list[$type] ?? '';

    }

    public static function getServicePriceTypeList($serviceType = null){
        $list = [
            self::SERVICE_PRICE_TYPE_URGE => '加急',
            self::SERVICE_PRICE_TYPE_INSURANCE => '保险',
            self::SERVICE_PRICE_TYPE_FACE => '人脸识别'
        ];
        return is_null($serviceType) ? $list : $list[$serviceType] ?? '';

    }


    public static function getEmployerStateList($state = null){
        $list = [
            self::EMPLOYER_STATE_UN_START => '未开始',
            self::EMPLOYER_STATE_UN_CONFIRM => '待帮手竞价',
            self::EMPLOYER_STATE_UN_RECEIVE => '待帮手接单',
            self::EMPLOYER_STATE_RECEIVE => '已接单',
            self::EMPLOYER_STATE_CANCEL => '已取消',
            self::EMPLOYER_STATE_DELIVERED => '已交付',
            self::EMPLOYER_STATE_COMPLETE => '已完成',
            self::EMPLOYER_STATE_REFUSE_DELIVERY => '拒绝交付'
        ];
        return is_null($state) ? $list : $list[$state] ?? '';
    }


    public static function getHelperStateList($state = null){
        $list = [
            self::HELPER_STATE_EMPLOYER_UN_CONFIRM => '待雇主确认',
            self::HELPER_STATE_RECEIVE => '已接单',
            self::HELPER_STATE_DELIVERED => '已交付',
            self::HELPER_STATE_CANCEL => '已取消',
            self::HELPER_STATE_COMPLETE => '已完成',
            self::HELPER_STATE_REFUSE_DELIVERY => '交付被拒'
        ];
        return is_null($state) ? $list : $list[$state] ?? '';
    }

    public static function employerUnModifyStateList(){
        return [
            self::EMPLOYER_STATE_RECEIVE ,
            self::EMPLOYER_STATE_CANCEL ,
            self::EMPLOYER_STATE_DELIVERED ,
            self::EMPLOYER_STATE_COMPLETE
        ];
    }

    /**
     * 没有已接单，是因为接单状态可能早于任务修改后支付异步回调
     *
     * @return array
     */
    public static function employerUnPayStateList(){
        return [
            self::EMPLOYER_STATE_CANCEL ,
            self::EMPLOYER_STATE_DELIVERED ,
            self::EMPLOYER_STATE_COMPLETE
        ];
    }


    public static function employerUnCompleteList(){
        return [
            self::EMPLOYER_STATE_UN_START,
            self::EMPLOYER_STATE_UN_RECEIVE,
            self::EMPLOYER_STATE_UN_CONFIRM,
            self::EMPLOYER_STATE_RECEIVE,
            self::EMPLOYER_STATE_DELIVERED,
            self::EMPLOYER_STATE_REFUSE_DELIVERY
        ];
    }

    public static function employerCompleteList(){
        return [
            self::EMPLOYER_STATE_CANCEL,
            self::EMPLOYER_STATE_COMPLETE
        ];
    }

    public static function helperCanReceiveList(){
        return [
            self::EMPLOYER_STATE_UN_RECEIVE,
            self::EMPLOYER_STATE_UN_CONFIRM
        ];
    }


    public static function helperUnCompleteList(){
        return [
            self::HELPER_STATE_EMPLOYER_UN_CONFIRM,
            self::HELPER_STATE_RECEIVE,
            self::HELPER_STATE_DELIVERED,
            self::HELPER_STATE_REFUSE_DELIVERY
        ];
    }

    public static function helperCompleteList(){
        return [
            self::HELPER_STATE_CANCEL,
            self::HELPER_STATE_COMPLETE
        ];
    }


    public static function getMainPriceChangeTypeList($type = null){
        $list = [
            self::PRICE_CHANGE_ORDER_PAY => 'pay_price',
            self::PRICE_CHANGE_MAKE_UP => 'change_price',
            self::PRICE_CHANGE_CONFIRM => 'confirm_price',
            self::PRICE_CHANGE_HELPER_OVERTIME => 'overtime_price',
            self::PRICE_CHANGE_HELPER_CANCEL => 'helper_cancel_price',
            self::PRICE_CHANGE_EMPLOYER_CANCEL => 'employer_cancel_price'

        ];
        return is_null($type) ? $list :  $list[$type] ?? '';
    }
    public static function getChangePriceIdentifyList($type = null){
        $serviceList = self::getServiceTypeList();
        $mainList = self::getMainPriceChangeTypeList();
        $list = $serviceList+$mainList;
        return is_null($type) ? $list :  $list[$type] ?? '';
    }

    public static function getServiceTypeList($type = null)
    {
        $list = [
            self::SERVICE_PRICE_TYPE_INSURANCE => 'insurance_price',
            self::SERVICE_PRICE_TYPE_FACE => 'face_price',
            self::SERVICE_PRICE_TYPE_URGE => 'urgent_price'
        ];
        return is_null($type) ? $list :  $list[$type] ?? '';
    }


    public static function getEmployerCancelTypeList($type = null){
        $list = [
            self::CANCEL_TYPE_EMPLOYER_LEAVE => '临时有事',
            self::CANCEL_TYPE_EMPLOYER_SOLUTION => '已找到解决方式',
            self::CANCEL_TYPE_EMPLOYER_OTHER => '其它原因'
        ];
        return is_null($type) ? $list :  $list[$type] ?? '';
    }


    public static function getEmployerRefuseTypeList($type = null){
        $list = [
            self::REFUSE_TYPE_EMPLOYER_LEAVE => '临时有事',
            self::REFUSE_TYPE_EMPLOYER_UN_STANDARDS => '没有达到标准',
            self::REFUSE_TYPE_EMPLOYER_OTHERS => '其它原因'
        ];
        return is_null($type) ? $list :  $list[$type] ?? '';
    }


    public static function getHelperCancelTypeList($type = null){
        $list = [
            self::CANCEL_TYPE_HELPER_LEAVE => '临时有事,无法完成任务',
            self::CANCEL_TYPE_HELPER_BAD_WEATHER => '恶劣天气',
            self::CANCEL_TYPE_HELPER_CONTRACT => '联系不上雇主',
            self::CANCEL_TYPE_HELPER_MODIFY => '雇主任务信息不明确，临时修改任务',
            self::CANCEL_TYPE_HELPER_OTHER => '其它原因'

        ];
        return is_null($type) ? $list :  $list[$type] ?? '';
    }

    public static function getCancelTypeList($type = null){
        $employerTypeList = self::getEmployerCancelTypeList();
        $helperTypeList = self::getHelperCancelTypeList();
        $cancelTypeList = $employerTypeList + $helperTypeList;
        $cancelTypeList[self::CANCEL_TYPE_EMPLOYER_COMPETITION_FAIL] = '竞价失败取消';
        $cancelTypeList[self::CANCEL_TYPE_HELPER_QUOTED] = '帮手取消报价';
        $cancelTypeList[self::CANCEL_TYPE_EMPLOYER_SYSTEM] = '系统取消';
        return is_null($type) ? $cancelTypeList :  $cancelTypeList[$type] ?? '';

    }
}
