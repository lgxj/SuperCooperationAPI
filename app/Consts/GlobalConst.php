<?php
namespace App\Consts;


class GlobalConst
{
    const SUCCESS = 0;
    const SUCCESS_MSG = 'success';
    const FAIL = 1;
    const FAIL_MSG = 'fail';

    const PAGE_SIZE = 10;   // 分页数据默认每页数量

    const KM_TO_M = 1000;
    const ACCESS_TOKEN_NOT_EXIST = 10000;
    const ACCESS_TOKEN_EMPTY = 10001;

    const RAND_DB_SWITCH = 1000000;
    const NEAR_BY_USER = 10000;

    const PERCENTILE = 100;
    const DATE_FORMAT = 'Y-m-d H:i:s';

    const SYSTEM_MANAGE = 1;    // 系统类型：总管理后台
    const SYSTEM_AGENT = 2;     // 系统类型：代理商后台
    const SYSTEM_STORE = 3;     // 系统类型：店铺管理后台

    const DEFAULT_CUSTOMER_ID = 1;  // 默认客服ID

    const CLIENT_WEB_ADMIN = 1;
    const CLIENT_WEB_AGENT = 2;
    const CLIENT_WEB_STORE = 3;
    const CLIENT_APP_STORE = 4;
    const CLIENT_WX_APP_USER = 5;
    const CLIENT_WX_H5_USER = 6;
    const CLIENT_H5_USER = 7;
    const CLIENT_APP_RIDER = 8;
    const CLIENT_SYSTEM_CLI = 99;

}
