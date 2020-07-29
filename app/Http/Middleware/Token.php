<?php


namespace App\Http\Middleware;


use App\Consts\GlobalConst;

/**
 * app登录 middleware
 *
 * Class Token
 * @package App\Http\Middleware
 */
class Token
{

    protected $blackList = [
        'User'=>[
            'User'=>['loginWithPassword','register','resetPassword','sendCode','loginWithPhoneCode','quickRegisterAndLogin','thirdPartyAppLogin']
        ],
        'Pool'=>[
            'Address'=>['provinces','associateNextLevel','allParent','getAreaByCode','calcAltitude','getByName','altitude'],
            'Article' => ['getList', 'getDetail'],
            'Index' => ['map']
        ],
        'Common' => [
            'Config' => ['index'],
            'Record' => ['transform'],
            'Upgrade' => ['index']
        ],
        'Trade.Order' => [
            'Search'=>['options','search','nearByTask','nearByHelper'],
            'Index' => ['config','detail']
        ]

    ];
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        list($module,$controller,$action) = parseActionName();
        $module = $this->blackList[$module] ?? [];
        $controller = $module[$controller] ?? [];
        $isContinue = in_array($action,$controller);
        $accessToken = trim($request->header('SC-ACCESS-TOKEN'));
        if(empty($accessToken) && !$isContinue){
            return out(GlobalConst::ACCESS_TOKEN_EMPTY, '登录失败，不存在登录凭证', false);
        }
        $login = \Cache::get($accessToken);
        if(empty($login) && !$isContinue){
            return out(GlobalConst::ACCESS_TOKEN_EMPTY, '登录失效，请重新登录', false);
        }
        $request->offsetSet('userLogin',$login ?? []);
        return $next($request);

    }


}
