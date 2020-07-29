<?php
namespace App\Providers;

use App\Exceptions\BusinessException;
use App\Utils\Push\GeTui;
use Illuminate\Support\ServiceProvider;

class PushServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('push', function ($app) {
            if (config('push.default') == 'getui') {
                return new GeTui();
            }
            throw new BusinessException('推送驱动不存在');
        });
    }
}
