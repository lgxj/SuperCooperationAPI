<?php


namespace App\Consts\ErrorCode;


/**
 * 2位平台号+2位模块号+3位功能+3位业务码号
 * 内部平台号为10,电话模块11，基础功能为01，业务错误001
 *
 *
 * Class MessageErrorCode
 * @package App\Consts\ErrorCode
 */
class TelephoneErrorCode
{
    const PARAM_ERROR = 101101001;
    const PARAM_TEL_NOT_EXIST = 101101002;

}
