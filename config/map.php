<?php
return [
    'type'=>'AMap',
    'amap'=>[
        'secret'=>'9224fd7d3332ad284b08804889f7e2e3d5e',//高德的key
        'secret_sign' => 'c2b9c3b3d0803cc641541dd0739bc09dde',//高德的key对应的数字签名
        'url'=> 'http://restapi.amap.com/',
        'yun_tu_url'=>'https://yuntuapi.amap.com/'
    ],
    'tencent'=>[
        'secret'=>'RGPBZ-2GSLX-NCPJE-75TKA-LLNXH-TLBNZ',//腾讯的key
        'secret_sign' => 'KEvgqyZ3Uw2YpjHt63sY6SzA6Is9XtRbW4i37',//腾讯的的key对应的数字签名
        'url'=> 'http://restapi.amap.com/',
        'yun_tu_url'=>'https://apis.map.qq.com/'
    ],
    'enable' => 'tencent'
];



