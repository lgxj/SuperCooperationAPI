<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // https://www.jianshu.com/p/2aca344ef917
    'weibo' => [
        'client_id' => env('WEIBO_KEY'),
        'client_secret' => env('WEIBO_SECRET'),
        'redirect' => env('WEIBO_REDIRECT_URI'),
    ],

    // https://learnku.com/articles/22307
    'qq' => [
        'client_id' => env('QQ_KEY'),
        'client_secret' => env('QQ_SECRET'),
        'redirect' => env('QQ_REDIRECT_URI'),
    ],

    // https://blog.csdn.net/weixin_34220179/article/details/91368321
    'weixin' => [
        'client_id'     => env('MACAO_WECHAT_OFFICIAL_ACCOUNT_APPID', 'YOUR_AppID'),
        'client_secret' => env('MACAO_WECHAT_OFFICIAL_ACCOUNT_SECRET', 'YOUR_AppSecret'),
        'redirect'      => 'http://www.top-booking.com/weixin/callback',

        # 这一行配置非常重要，必须要写成这个地址。
        'auth_base_uri' => 'https://公众号中设置的域名/connect/qrconnect',

    ],

    // https://www.cnblogs.com/lishanlei/p/10073878.html
    'weixinweb' => [
        'client_id' => env('WEIXIN_KEY'),
        'client_secret' => env('WEIXIN_SECRET'),
        'redirect' => env('WEIXIN_REDIRECT_URI'),
    ],
];
