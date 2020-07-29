<?php
return [
    'default'=>'getui',
    'logoName' => env('PUSH_LOGO_NAME', 'logo.png'),
    'logoUrl' => env('PUSH_LOGO_URL', ''),
    'sound' => env('PUSH_SOUND', ''),
    'aliasPrefix' => env('APP_ENV'),
    'getui'=>[
        'appId' => env('PUSH_GETUI_APP_ID', 'e1RwWr2KsCLV5EMS9gI3Bey46'),
        'appSecret' => env('PUSH_GETUI_APP_SECRET', 'iZrhrNdqeTMAT3h3NkBNsx66'),
        'appKey' => env('PUSH_GETUI_APP_KEY', 'ZIYK6oRh1075wsVvS69XG9e2'),
        'appPackageName' => env('PUSH_GETUI_APP_PACKAGE_NAME', 'cc.lgxj.help'),
        'masterSecret' => env('PUSH_GETUI_MASTER_SECRET', 'gMq4qpXdpsH06FQQA1tuX1N37'),
        'host' => env('PUSH_GETUI_HOST', 'http://sdk.open.api.igexin.com/apiex.htm'),
    ]
];
