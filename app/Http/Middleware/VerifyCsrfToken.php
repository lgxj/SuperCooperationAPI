<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Indicates whether the XSRF-TOKEN cookie should be set on the response.
     *
     * @var bool
     */
    protected $addHttpCookie = true;

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'callback/weixin/notify',
        'callback/weixin/refund',

        'callback/alipay/notify',
        'callback/alipay/return',
        'callback/alipay/grant',

        'callback/im/notify',
        'callback/phone/notify.record',
        'callback/phone/notify.status',
        'callback/phone/notify.hangup'
    ];
}
