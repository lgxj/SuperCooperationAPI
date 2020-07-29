<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        if(App()->environment ('local')){
           $this->mapIpRoutes();
        }
        $this->mapApiRoutes();
        $this->mapAdminRoutes();
        $this->mapWebRoutes();

        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace('App\Web\Controllers')
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        $domain = $this->getDomainPrefix('api').'.'.env('APP_DOMAIN');
        Route::domain($domain)
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }

    protected function mapAdminRoutes()
    {
        $domain = $this->getDomainPrefix('admin-api').'.'.env('APP_DOMAIN');
        Route::domain($domain)
            ->middleware('admin-api')
            ->namespace('App\Admin\Controllers')
            ->group(base_path('routes/admin.php'));
    }

    protected function mapIpRoutes()
    {
        $domain = $_SERVER['SERVER_ADDR'] ?? '192.168.0.120';
        Route::domain($domain)
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }

    protected function getDomainPrefix($type){
        $isOnline = App()->environment ('production');
        if($type == 'api'){
            return $isOnline ? 'sc-api' : 'api';
        }elseif($type == 'admin-api'){
            return $isOnline ? 'sc-admin-api' : 'admin-api';
        }
        return '';
    }
}
