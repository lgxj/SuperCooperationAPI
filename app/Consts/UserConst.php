<?php


namespace App\Consts;


class UserConst
{

    /**
     * 表示雇主
     */
    const TYPE_EMPLOYER = 0;
    /**
     * 表示帮手
     */
    const TYPE_HELPER = 1;

    const GRANT_LOGIN_TYPE_PHONE = 0;
    const GRANT_LOGIN_TYPE_WEIXIN = 1;
    const GRANT_LOGIN_TYPE_WEIXINWEB = 2;
    const GRANT_LOGIN_TYPE_QQ = 3;
    const GRANT_LOGIN_TYPE_WEIBO = 4;
    const GRANT_LOGIN_TYPE_EMAIL = 5;
    const GRANT_LOGIN_TYPE_ADMIN = 6;   // 后台管理账户
    const GRANT_LOGIN_TYPE_ALIPAY = 7;

    const CHANGE_LOG_TYPE_PASSWORD = 0;
    const CHANGE_LOG_TYPE_PHONE = 1;

    const LABEL_TYPE_EMPLOYER = 0;
    const LABEL_TYPE_HELPER = 1;

    const FACE_AUTH_TYPE_HELPER = 1;    // 扫脸业务类型：帮手认证
    const FACE_AUTH_TYPE_RECEIVE = 2;   // 扫脸业务类型：接单扫脸

    public static function thirdPartyLoginTypes (){
        return [
            self::GRANT_LOGIN_TYPE_QQ => 'qq',
            self::GRANT_LOGIN_TYPE_WEIXIN => 'weixin',
            self::GRANT_LOGIN_TYPE_ALIPAY => 'alipay'
        ];
    }

    public static function getThirdGrantLoginType($stringType){
        $types = self::thirdPartyLoginTypes();
        $types = array_flip($types);
        return $types[$stringType];
    }

}
