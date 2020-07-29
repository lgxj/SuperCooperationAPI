<?php
namespace App\Consts;


class ErrorConst
{
    const NO_TOKEN = 1001;              // 未登录
    const TOKEN_ERROR = 1002;           // Token错误（或过期或被其他人顶掉）
    const LOGIN_CACHE_ERROR = 1003;     // 登录Token缓存数据错误
    const LOGIN_USER_NOT_FOUND = 1004;  // 登录账户未找到
    const LOGIN_USER_FROZEN = 1005;     // 登录账户已被冻结
    const LOGIN_USER_NO_POWER = 1101;   // 登录账户没有访问权限

    const SIGN_ERROR = 1000;       // 签名错误
}
