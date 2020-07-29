<?php


namespace App\Http\Middleware;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * 日志系统访问校验
 *
 * Class LogViewer
 * @package App\Http\Middleware
 */
class LogViewer
{

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        if(!Cache::get('view_visit')){
           return '';
        }
        return $next($request);
    }
}
