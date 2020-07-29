<?php


namespace App\Http\Controllers\User;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AlipayController extends Controller
{

    public function grant(Request $request)
    {
        $token = trim($request->header('SC-ACCESS-TOKEN'));
        if(empty($token)){
            return fail([],'您没有权限操作');
        }
        $encryptToken = encrypt($token);
        $appId = config('pay.alipay.app_id');
        $redirectUri = 'https://sc.250.cn/callback/alipay/login';
        $url = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=' . $appId . '&scope=auth_user&redirect_uri=' .urlencode($redirectUri).'&state=' . $encryptToken;
        $appId = 20000067;
        // 添加安卓和苹果区别
        $xx = getDeviceType();
        if($xx == 'ios'){
            $url = 'alipay://platformapi/startapp?appId=' . $appId . '&url=' . urlencode($url);
        }else{
            $url = 'alipays://platformapi/startapp?appId=' . $appId . '&url=' . urlencode($url);
        }
        $response['grant_url'] = $url;
        return success($response);
    }

}
