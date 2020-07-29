<?php


namespace App\Consts;


class CertificateConst
{

    const DRIVER_LICENSE = 0;
    const NURSING_LICENSE = 1;
    const TEACHER_LICENSE = 2;
    const DOCTOR_LICENSE = 3;
    const KINDERGARTEN_LICENSE=4;
    const CET4_LICENSE = 5;
    const CET6_LICENSE = 6;
    const UNIVERSITY_DIPLOMA_LICENSE = 7;
    const HEALTH_LICENSE = 8;
    const ACCOUNTING_LICENSE = 9;
    const OFFICERS = 10;
    const DIETITIAN=11;
    const REWARD = 12;
    const ARCHITECTURAL=13;

    public static function licenseList(){
        return [
            self::DRIVER_LICENSE => '驾驶证',
            self::NURSING_LICENSE => '护理证',
            self::TEACHER_LICENSE => '教师资格证',
            self::DOCTOR_LICENSE => '医师证',
            self::KINDERGARTEN_LICENSE => '幼师证',
            self::CET4_LICENSE => '英语四级证',
            self::CET6_LICENSE => '英语六级证',
            self::UNIVERSITY_DIPLOMA_LICENSE => '大学毕业证',
            self::HEALTH_LICENSE => '健康证',
            self::ACCOUNTING_LICENSE => '会计证',
            self::OFFICERS => '军官证',
            self::DIETITIAN =>'营养师证',
            self::ARCHITECTURAL => '建筑工程',
            self::REWARD => '获奖证明',
        ];
    }

}
