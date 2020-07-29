<?php


namespace App\Web\Controllers\Weixin;


use App\Web\Controllers\ScController;
use Illuminate\Http\Request;

class IndexController extends ScController
{
    public function token(Request $request){
        $inputSign = $request->get('signature');
        $timestamp = $request->get('timestamp');
        $nonce     = $request->get('nonce');

        // ninghao 是我在微信后台手工添加的 token 的值
        $token = 'g123abc90bhyu3fh3';

        // 加工出自己的 signature
        $signature = array($token, $timestamp, $nonce);
        sort($signature, SORT_STRING);
        $signature = implode($signature);
        $signature = sha1($signature);

        // 用自己的 signature 去跟请求里的 signature 对比
        if ($inputSign != $signature) {
            return false;
        }
        return true;
    }

    public function index(Request $request){
        return $request->get('echostr');
    }
}
