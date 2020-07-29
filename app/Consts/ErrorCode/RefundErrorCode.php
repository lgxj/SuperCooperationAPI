<?php


namespace App\Consts\ErrorCode;

/**
 * 2位平台号+2位模块号+3位功能+3位业务码号
 * 内部平台号为10,退款模块06，支付务功能为01，业务错误001
 *
 *
 * Class RefundErrorCode
 * @package App\Consts\ErrorCode
 */
class RefundErrorCode
{
    const CHECK_TASK_ORDER_NO = 100601001;
    const CHECK_REFUND_PRICE_ERROR = 100601002;
    const CHECK_PAY_PRICE_ERROR = 100601003;
    const CHECK_REFUND_GREATER_PAY = 100601004;
    const CHECK_REFUND_GREATER_REAL_REFUND = 100601005;
    const CHECK_TASK_UN_PAY = 100601006;
    const CHECK_PAY_RECORD_NOT_EXIST = 100601007;
    const CHECK_REFUND_CHANNEL_SUPPORT = 100601008;
    const CHECK_COMPENSATE_RECORD_NOT_EXIST = 100601009;

}
