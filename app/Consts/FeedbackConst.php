<?php


namespace App\Consts;


class FeedbackConst
{
    const TYPE_FUNCTION = 1;
    const TYPE_SECURE = 2;
    const TYPE_SUGGEST = 3;
    const TYPE_OTHER = 4;


    public static function getTypeList()
    {
        return [
            self::TYPE_FUNCTION => '功能异常',
            self::TYPE_SECURE => '安全问题',
            self::TYPE_SUGGEST => '产品建议',
            self::TYPE_OTHER => '其它问题'
        ];
    }

    public static function getTypeDesc($type)
    {
        $list = self::getTypeList();
        return $list[$type] ?? '';
    }
}
