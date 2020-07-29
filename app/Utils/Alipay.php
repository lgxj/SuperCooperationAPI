<?php


namespace App\Utils;


use Illuminate\Support\Facades\Log;

class Alipay
{

    /**
     * @var \AopClient
     */
    protected $aop;

    protected $appId;

    private static $errorMsg;

    public function __construct($appId = '')
    {
        $this->appId = $appId;
        $this->aop = $this->getAop();
    }

    /**
     * auth_code换取access_token
     * @param $auth_code
     * @return bool
     * @throws \Exception
     */
    public function systemOauthToken($auth_code)
    {
        require_once app_path('Utils/Aop/Request/AlipaySystemOauthTokenRequest.php');
        $request = new \AlipaySystemOauthTokenRequest();
        $request->setCode($auth_code);
        $request->setGrantType('authorization_code');
        $response = $this->aop->execute($request);
        return $this->parseResponse($response, 'alipay_system_oauth_token_response', false);
    }

    /**
     * 获取授权用户信息
     * @param $accessToken
     * @return bool
     * @throws \Exception
     */
    public function userInfoShare($accessToken)
    {
        require_once app_path('Utils/Aop/Request/AlipayUserInfoShareRequest.php');
        $request = new \AlipayUserInfoShareRequest();
        $response = $this->aop->execute($request, $accessToken);
        return $this->parseResponse($response, 'alipay_user_info_share_response');
    }



    /**
     * 构造支付宝AOP客户端
     * @return \AopCertClient
     */
    protected function getAop()
    {
        require_once app_path('Utils/Aop/AopCertClient.php');
        $aop = new \AopCertClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $this->appId ?: config('pay.alipay.app_id');
        $aop->signType = 'RSA2';
        $aop->rsaPrivateKey = config('pay.alipay.private_key');
        $aop->alipayrsaPublicKey = $aop->getPublicKey(config('pay.alipay.ali_public_key'));
        $aop->apiVersion = '1.0';
        $aop->postCharset='utf-8';
        $aop->format='json';
        $aop->isCheckAlipayPublicCert = true;//是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内
        $aop->appCertSN = $aop->getCertSN(config('pay.alipay.app_cert_public_key'));//调用getCertSN获取证书序列号
        $aop->alipayRootCertSN = $aop->getRootCertSN(config('pay.alipay.alipay_root_cert'));//调用getRootCertSN获取支付宝根证书序列号
        return $aop;
    }

    /**
     * 解析结果
     * @param $response
     * @param $action
     * @param bool $checkCode
     * @return bool
     */
    protected function parseResponse($response, $action, $checkCode = true)
    {
        if (isset($response->error_response)) {
            Log::error('支付宝接口调用失败：' . json_encode($response->error_response, 320));
            return false;
        }
        $res = $response->{$action} ?? false;
        if ($checkCode && $res && $res->code != 10000) {
            self::setError($res->sub_msg);
            Log::error('支付宝接口调用出错：' . json_encode($res, 320));
            return false;
        }
        return $res;
    }

    /**
     * 设置错误信息
     * @param $msg
     */
    private static function setError($msg)
    {
        self::$errorMsg = $msg;
    }

    /**
     * 获取错误信息
     * @return mixed
     */
    public static function getErrorMsg()
    {
        return self::$errorMsg;
    }




}
