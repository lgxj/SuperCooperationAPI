<?php


namespace App\Consts\ErrorCode;

/**
 * 2位平台号+2位模块号+3位功能+3位业务码号
 * 内部平台号为10,第三方http请求模块11，oss功能为01，业务错误001
 *
 *
 * Class RequestErrorCode
 * @package App\Consts\ErrorCode
 */
class RequestErrorCode
{
    const ALIYUN_OSS_FAILED = 101101001;

    const YUN_TU_FAILED = 101102001;
    const YUN_TU_PARAM_ERROR = 101102002;
    const YUN_TU_LNG_LAT_EMPTY = 101102003;
    const YUN_TU_QUERY_RADIOS_LIMIT = 101102004;
    const YUN_TU_DELETE_LIMIT = 101102005;
    const YUN_TU_INFO = 101102006;

    const GAO_DE_FAILED = 101103001;

}
