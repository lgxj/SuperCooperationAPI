<?php


namespace App\Consts\ErrorCode;


/**
 * 2位平台号+2位模块号+3位功能+3位业务码号
 * 内部平台号为10,基础池模块09，fee功能为01，业务错误001
 *
 *
 * Class FeeErrorCode
 * @package App\Consts\ErrorCode
 */
class FeeErrorCode
{
    const CHECK_VALIDATION_ERROR = 100901001;

}
