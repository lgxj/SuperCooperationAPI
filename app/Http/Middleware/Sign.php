<?php

namespace App\Http\Middleware;

use App\Consts\ErrorConst;
use App\Consts\GlobalConst;
use Closure;

/**
 * app接口 访问安全校验
 *
 * Class Sign
 * @package App\Http\Middleware
 */
class Sign
{
    protected $appMap = [
        'app9321c3096489b2aab073ee479ce61f10'=>'8a50ad7ac3fcc4636e703aef111786d9', //uniapp key/value
        'app551ac64a61ebaadbf645274d334f9153'=>'a025ea3ff65610c8a95b6e7cfde500da',//admin key/value
    ];
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $httpParam = $request->input();
        $secretApp = $request->header('SC-API-APP');
        if(!in_array($secretApp,array_keys($this->appMap))){
            return out(GlobalConst::FAIL, 'Unauthorized, invalid client', false);
        }
        $secretText = $this->makeParamSource($httpParam);
        $verifySign =  $this->sign($secretText,$this->appMap[$secretApp]);
        $sign = $request->header('SC-API-SIGNATURE');
        if(App()->environment ('local')){
            \Log::info("local confirm action:{$request->route()->getActionName()} sign:{$verifySign} : request sign:{$sign}");
            return $next($request);//测试时去掉
        }
        if($sign === $verifySign){
            return $next($request);
        }
        return out(ErrorConst::SIGN_ERROR, 'Unauthorized, invalid signature', false);
    }


    protected  function makeParamSource(array $params)
    {
        ksort($params);//按照关键码从小到大排序
        $buffer = '';
        foreach ($params as $key => $value) {
            if (empty($value)) continue;
            if (is_array($value)) {
                $value = json_encode($value,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $buffer .= $key . '=' . $value . '&';
        }
        $buffer = substr_replace($buffer, '', -1, 1);
        return rawurlencode($buffer);
    }

    protected function sign($text, $appSecretKey)
    {
        try {
            $hash = hash_hmac('sha1', $text, $appSecretKey, true);
            $sign = base64_encode($hash);
        } catch (\Exception $e){
            return out(GlobalConst::FAIL, 'Unauthorized, server signature fail', false);
        }
        return $sign;
    }
}
