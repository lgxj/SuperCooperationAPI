<?php


namespace App\Consts;


class SmsConst
{
    const SMS_ALIYUN_LOGIN = 'SMS_183390965';
    const SMS_ALIYUN_REGISTER = 'SMS_183390963';
    const SMS_ALIYUN_PASSWORD = 'SMS_183390962';
    const SMS_ALIYUN_PAY_PASSWORD = 'SMS_183267386';
    const SMS_ALIYUN_INFO_UPDATE = 'SMS_183390961';

    const CODE_TYPE_PHONE = 0;
    const CODE_TYPE_EMAIL = 1;

    const CHANNEL_ALIYUN = 0;

    const BUSINESS_TYPE_REGISTER = 0;
    const BUSINESS_TYPE_PASSWORD = 1;
    const BUSINESS_TYPE_LOGIN = 2;
    const BUSINESS_TYPE_BANK_PHONE = 3;
    const BUSINESS_TYPE_PAY_PASSWORD = 4;
    const BUSINESS_TYPE_UPDATE_PHONE = 5;

    public static function inChannel($channel){
        return in_array($channel,[self::CHANNEL_ALIYUN]);
    }

    public static function inCodeType($codeType){
        return in_array($codeType,[self::CODE_TYPE_PHONE,self::CODE_TYPE_EMAIL]);
    }

    public static function inBusinessType($businessType){
        return in_array($businessType,[self::BUSINESS_TYPE_REGISTER,self::BUSINESS_TYPE_LOGIN,self::BUSINESS_TYPE_PASSWORD,self::BUSINESS_TYPE_BANK_PHONE,self::BUSINESS_TYPE_PAY_PASSWORD,self::BUSINESS_TYPE_UPDATE_PHONE]);
    }

    public static function getTemplate($codeType,$businessType,$channel){
        $templates = self::getTemplates();
        return isset($templates[$codeType][$channel][$businessType]) ? $templates[$codeType][$channel][$businessType] : '';
    }

    public static function getTemplates(){
        return [
           self::CODE_TYPE_PHONE =>[
               self::CHANNEL_ALIYUN=>[
                   self::BUSINESS_TYPE_REGISTER=>self::SMS_ALIYUN_REGISTER,
                   self::BUSINESS_TYPE_LOGIN=>self::SMS_ALIYUN_LOGIN,
                   self::BUSINESS_TYPE_PASSWORD=>self::SMS_ALIYUN_PASSWORD,
                   self::BUSINESS_TYPE_PAY_PASSWORD => self::SMS_ALIYUN_PAY_PASSWORD,
                   self::BUSINESS_TYPE_BANK_PHONE => self::SMS_ALIYUN_INFO_UPDATE,
                   self::BUSINESS_TYPE_UPDATE_PHONE => self::SMS_ALIYUN_INFO_UPDATE
               ]
           ],
            self::CODE_TYPE_EMAIL =>[
            ]
        ];
    }
}
