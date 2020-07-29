<?php


namespace App\Consts\ErrorCode;

/**
 * 2位平台号+2位模块号+3位功能+3位业务码号
 * 内部平台号为10,短信模块02，登录注册功能为01，业务错误001
 *
 *
 * Class SmsErrorCode
 * @package App\Consts\ErrorCode
 */
class SmsErrorCode
{
    const  SMS_CODE_TYPE_ERROR =  100201001;
    const  SMS_CODE_BUSINESS_TYPE_ERROR = 100201002;
    const  SMS_CODE_TEMPLATE_ERROR = 100201003;
    const  SMS_CODE_CHANNEL_ERROR = 100201004;
    const  SMS_CODE_ACCOUNT_ERROR = 100201005;
    const  SMS_CODE_TIME_MINUTE_LIMIT = 100201006;
    const  SMS_CODE_TIME_TODAY_LIMIT = 100201007;
    const  SMS_CODE_SEND_FAILED = 100201008;

    const  SMS_CODE_VERIFY_NOT_EXIST = 100202001;
    const  SMS_CODE_VERIFY_ERROR = 100202002;
    const  SMS_CODE_VERIFY_EXPIRED = 100202003;
    const  SMS_CODE_VERIFY_USED = 100202004;

}
