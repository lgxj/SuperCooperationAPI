<?php

return [
    'alipay' => [
        // 支付宝开放平台注册的App应用APPID
        'app_id' => '2021000194694089',

        // 支付宝异步通知地址
        'notify_url' => 'https://sc.250.cn/callback/alipay/notify',//

        // 支付成功后同步通知地址
        'return_url' => 'https://sc.250.cn/callback/alipay/return',

        // 阿里公共密钥，验证签名时使用
        'ali_public_key' =>  config_path('alipay_cert/alipayCertPublicKey_RSA2.crt'),

        // 自己的私钥，签名时使用
        'private_key' => 'MIIEvAsIDBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCGwJnVAoYbyW/Knou/hIV5iDOjNyYRjV6qE7tbCp2UKCEYSnLStxZ80JvqWL/qIXgy13ooEPGTms/uIk6mjIy97NdV/nDRTO0EE2wib8V6vYgKOAfFVTjcdEzgKfF/mDAbPJiO+BAG6dLoXQNNdkNO5LwXNvEznr1r03N+ttwdG6pWhZtKxep+zlQpgBimeL6UI94xUjq6f+42Mj9IYtPbfIoWy/r+PMW2NWfWrCE0Q4xIWK9C6Hw7d4PvC+xHl0g47hz91VWlCjNpCKKCaveKsiqshFeMcNfLizTPfuNrGo4QPjaIxLL9TjN0PCEpkHmfVswCimXZ51Jr8jQfAgMBAAECggEAHxttJ7A6d0WsKfEpv59/FIwtp3r+rcSd2wXhy/PKrmM0ke1dBFR/qRFqfas7wak8c+uYntu2S0+3PFMiMQfZWS8XRysvJRujtVlimJPNWbMZ+FiZIq+YR6ShyLqMWJ82IcdnK5J0gWSghogJ6Rw3u/UWGqtRBGbpjYoSnkak/dYw/CJjsuqBk9893nQKXeUmGcB1ojxhsDXsjr4QixO2LoM14Wa6Ff+kPWLkJFhQqch67IzYXkCTNiYIcA6XLPxwcLB03UOlmKfs7OvXQ5JfpZbWAcidSzL7vlVrPbbsGpK5ymkOHdhWni/WYZUha5zB8Zy243HnLUwZhavmzUTo6QKBgQC86IOlUflW+o6vvPx5sFm/DlKPS+ZwQz2nSeD/6SgO1eEDzN64t+xvLMylCJf+olIqJRXkfBRsiaRYiCP/0e7ZoIKnCvru7yfnj3owgw5tnVyHmLdznc0HchyTj+RAY0bITA0mMTevGByt5rmtcXwaUgd/mNgc/M9eN4Rw7+BMVQKBgQC2nD8Vuw5QfXucvFa8+xQb6x74QoDg3YVo+Tb2Z11i2gHQhUHcMBdDcice8r84IasFtmWhyg0KAjnbwBE/ucJVn9cPpTw/biNCT9N0s/bv6Gpgb1rCF92fk3tYO0bygJQD9DXsR+UixGzam+j+aLbC7l4Stet+N+n9MqYVZVUyowKBgEOeqzjrLeh/icHIUUux334vP0hB7/uxZglSvbJ9IDSnRINau0K9u2lUTPCqMdYY/nZNjheafqkXX/e8y74PxIKsHPh0SrxqaQtPZXql/u0nze5PsSM2kiSfKTF86URPBRA/gNlx9q+7XtC07TPzkmvNfxeie4Fs0UG1d7gBdXV9AoGAV+vOq+XONI7WL1a25HJ28iI4XQuYBaxiiXJENkr6OZgBe6ZNWXiGGuEhWNNDogED2NJDCKzBrmn39Yf6RhZoLNWytEO4SEn9C/ZnNy9W25epcBjtN7pJ6IUXxBl2RVgG7Ahu1f6foie0yWR7v9Im/J/MB3IzmifNryztriuF48UCgYBbG+sOmq2IHdGiWQ/hg8GvhALFv73x4vO5FKenmdYbMsdGRePu7+ncnGSd54sqinAS9NF/5P4vAos4HDSDFYGnCNcOFgQsskGWghEL0fk63P6bI9eRERGQmbTZI4LwxE9tpuTdSyylDkGb2tg/t6XRvA8Xz952of/07dXyQqtrIfSsA==',

        // 使用公钥证书模式，请配置下面两个参数，同时修改ali_public_key为以.crt结尾的支付宝公钥证书路径，如（./cert/alipayCertPublicKey_RSA2.crt）
         'app_cert_public_key' => config_path('alipay_cert/appCertPublicKey.crt'), //应用公钥证书路径
         'alipay_root_cert' => config_path('alipay_cert/alipayRootCert.crt'), //支付宝根证书路径

        // optional，默认 warning；日志路径为：sys_get_temp_dir().'/logs/yansongda.pay.log'
        'log' => [
            'file' => storage_path('logs/laravel.log'),
            'type' => 'daily', // optional, 可选 daily.
        //  'level' => 'debug'
        //  'max_file' => 30,
        ],

        // optional，设置此参数，将进入沙箱模式
        // 'mode' => 'dev',
    ],

    'wechat' => [
        // 公众号 APPID
        'app_id' => 'wx357c72a9b5eef8e58',

        // 小程序 APPID
        'miniapp_id' => 'wx6e79e049f849abdc5',

        // APP 引用的 appid
        'appid' => 'wx41fd0dc5c94fe1ae',

        // 微信支付分配的微信商户号
        'mch_id' => '145635948311',

        // 微信支付异步通知地址
        'notify_url' => 'https://sc.250.cn/callback/weixin/notify',

        // 微信支付签名秘钥
        'key' => '32ab4fe8k90a2324rtAb9w239UYB3GE30a3c',

        // 客户端证书路径，退款、红包等需要用到。请填写绝对路径，linux 请确保权限问题。pem 格式。
        'cert_client' => config_path('wechat_cert/apiclient_cert.pem'),

        // 客户端秘钥路径，退款、红包等需要用到。请填写绝对路径，linux 请确保权限问题。pem 格式。
        'cert_key' => config_path('wechat_cert/apiclient_key.pem'),

        // optional，默认 warning；日志路径为：sys_get_temp_dir().'/logs/yansongda.pay.log'
        'log' => [
            'file' => storage_path('logs/laravel.log'),
            'type' => 'daily', // optional, 可选 daily.
            //  'level' => 'debug'
        //  'max_file' => 30,
        ],

        // optional
        // 'dev' 时为沙箱模式
        // 'hk' 时为东南亚节点
        // 'mode' => 'dev',
    ],
];
