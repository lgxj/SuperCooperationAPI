<?php


namespace App\Consts\ErrorCode;

/**
 * 2位平台号+2位模块号+3位功能+3位业务码号
 * 内部平台号为10,提现模块07，支付务功能为01，业务错误001
 *
 *
 * Class WithdrawErrorCode
 * @package App\Consts\ErrorCode
 */
class WithdrawErrorCode
{

    const CHECK_USER_ERROR = 100701001;
    const CHECK_PRICE_ERROR = 100701002;
    const CHECK_CHANNEL_ERROR = 100701003;
    const CHECK_USER_LOCKED = 100701004;
    const CHECK_MIN_PRICE = 100701005;
    const CHECK_STATE_COMPLETE = 100701006;
    const CHECK_ACCOUNT_ERROR = 100701007;
    const CHECK_BALANCE_NOT_ENOUGH = 100701008;
    const CHECK_BANK_NOT_SUPPORT = 100701009;
    const CHECK_VALIDATION_ERROR = 100701010;
    const WITHDRAW_FAILED = 100701011;
}
