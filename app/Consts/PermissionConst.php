<?php


namespace App\Consts;

class PermissionConst
{

    //创建者角色ID为 0
    const CREATOR_ROLE_ID = 0;
    //初始化角色参照物的sub_id 为0
    const CREATOR_INIT_SUB_ID = 0;

    const SYSTEM_MANAGE = 1;    // 系统类型：总管理系统
    const SYSTEM_AGENT = 2;     // 系统类型：代理商系统
    const SYSTEM_STORE = 3;     // 系统类型：店铺系统

    public static function getSystem($system = null)
    {
        $systems = [
            self::SYSTEM_MANAGE => '平台',
            self::SYSTEM_AGENT => '代理商',
            self::SYSTEM_STORE => '店铺',
        ];

        return is_null($system) ? $systems : ($systems[$system] ?? '');
    }
}
