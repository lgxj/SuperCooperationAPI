<?php
namespace App\Utils;

use App\Exceptions\BusinessException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 腾讯运实名认证
 *
 * Class RealNameAuth
 * @package App\Utils
 */
class RealNameAuth
{
    protected $appId;
    protected $secret;
    protected $version;
    protected $keyLicence;

    static $accessTokenCacheKey = 'tencent_cloud_access_token_cache';   // access token缓存键名
    static $signTicketCacheKey = 'tencent_cloud_sign_ticket_cache';   // access token缓存键名
    static $idascApiAuth = 'https://idasc.webank.com/api/';   // 人脸核身API地址

    public function __construct()
    {
        $this->appId = config('tencentcloud.secretId');
        $this->secret = config('tencentcloud.secretKey');
        $this->keyLicence = config('tencentcloud.licence');
        $this->version = '1.0.0';
    }

    /**
     * ORC初始数据
     * @param $userId
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function ocrInit($userId)
    {
        $data = [
            'app_id' => $this->appId,
            'version' => $this->version,
            'userId' => (string)$userId,
            'ticket' => self::_getNonceTicket($userId),
            'nonceStr' => Str::random(32)
        ];

        $sign = self::_createSign($data);
        $data['sign'] = $sign;
        unset($data['ticket']);
        return $data;
    }

    /**
     * 获取OCR识别结果
     * @param $orderNo
     * @param int $getFile 是否获取图片。1：获取；0：不获取
     * @return bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getOcrResult($orderNo, $getFile = 1)
    {
        $data = [
            'app_id' => $this->appId,
            'version' => $this->version,
            'order_no' => (string)$orderNo,
            'sign_ticket' => self::_getSignTicket(),
            'nonce' => Str::random(32)
        ];
        $sign = self::_createSign($data);
        $data['sign'] = $sign;
        $data['get_file'] = $getFile;

        unset($data['sign_ticket']);

        $result = self::_request('server/getOcrResult', $data);
        if (!$result || $result['code'] || $result['result']['frontCode']) {
            Log::error('身份证识别失败', $result);
            return false;
        }

        return $result;
    }

    /**
     * 人脸核身初始化
     * @param $userId
     * @param $name
     * @param $idCard
     * @param $orderNo
     * @return array
     * @throws BusinessException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function verifyInit($userId, $name, $idCard, $orderNo)
    {
        $data = [
            'webankAppId' => $this->appId,
            'version' => $this->version,
            'userId' => (string)$userId,
            'ticket' => self::_getNonceTicket($userId),
            'nonceStr' => Str::random(32)
        ];

        $sign = self::_createSign($data);

        unset($data['ticket']);

        $data['sign'] = $sign;
        $data['orderNo'] = $orderNo;

        $options = $data;
        $options['name'] = $name;
        $options['idNo'] = $idCard;
        $options['orderNo'] = $orderNo;
        $options['sourcePhotoType'] = 1;

        unset($options['nonceStr']);

        $result = self::_request('server/getfaceid', $options, 'post');

        if (!$result || $result['code']) {
            throw new BusinessException($result['msg'] ?? '请求失败');
        }

        $data['faceId'] = $result['result']['faceId'];
        $data['keyLicence'] = $this->keyLicence;

        return $data;
    }

    /**
     * 人脸核身结果检查
     * @param $orderNo
     * @param int $getFile 是否需要获取人脸识别的视频和文件。1：返回视频和照片；2：返回照片；3：返回视频；其他：不返回
     * @return bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getVerifyResult($orderNo, $getFile = 2)
    {
        $data = [
            'app_id' => $this->appId,
            'version' => $this->version,
            'order_no' => (string)$orderNo,
            'sign_ticket' => self::_getSignTicket(),
            'nonce' => Str::random(32)
        ];
        $sign = self::_createSign($data);
        $data['sign'] = $sign;
        $data['get_file'] = $getFile;

        unset($data['sign_ticket']);

        $result = self::_request('server/sync', $data);
        if (!$result || $result['code']) {
            Log::error('人脸识别失败', $result);
            return false;
        }

        return $result;
    }

    /**
     * 获取access token
     * @return bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function _getAccessToken()
    {
        if (Cache::has(self::$accessTokenCacheKey)) {
            return Cache::get(self::$accessTokenCacheKey);
        }

        $data = [
            'app_id' => $this->appId,
            'secret' => $this->secret,
            'version' => $this->version,
            'grant_type' => 'client_credential'
        ];
        $result = self::_request('oauth2/access_token', $data);
        if (!$result || $result['code']) {
            Log::error('请求腾讯云获取access token失败', $result);
            return false;
        }

        Cache::add(self::$accessTokenCacheKey, $result['access_token'], now()->addMinutes(60));

        return $result['access_token'];
    }

    /**
     * 获取nonce ticket
     * @param $userId
     * @return bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function _getNonceTicket($userId)
    {
        $data = [
            'app_id' => $this->appId,
            'secret' => $this->secret,
            'version' => $this->version,
            'access_token' => self::_getAccessToken(),
            'type' => 'NONCE',
            'user_id' => $userId
        ];
        $result = self::_request('oauth2/api_ticket', $data);
        if (!$result || $result['code']) {
            Log::error('请求腾讯云获取nonce ticket失败', $result);
            return false;
        }

        return $result['tickets'][0]['value'] ?? false;
    }

    /**
     * 获取sign ticket
     * @return bool|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function _getSignTicket()
    {
        $accessToken = self::_getAccessToken();

        // sign ticket依赖access token，所以缓存key拼接上access token
        $key = self::$signTicketCacheKey . '-' . $accessToken;
        if (Cache::has($key)) {
            return Cache::get($key);
        }

        $data = [
            'app_id' => $this->appId,
            'version' => $this->version,
            'access_token' => self::_getAccessToken(),
            'type' => 'SIGN'
        ];
        $result = self::_request('oauth2/api_ticket', $data);
        if (!$result || $result['code']) {
            Log::error('请求腾讯云获取sign ticket失败', $result);
            return false;
        }

        $signTicket = $result['tickets'][0]['value'];
        if ($signTicket) {
            Cache::add($key, $signTicket, now()->addMinutes(60));
            return $signTicket;
        }

        return false;
    }

    /**
     * 生成签名
     * @param $data
     * @return string
     */
    protected function _createSign($data)
    {
        $data = array_values($data);
        asort($data);
        $str = implode('', $data);
        return strtoupper(sha1($str));
    }

    /**
     * 请求腾讯云接口
     * @param $action
     * @param $data
     * @param string $method
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function _request($action, $data, $method = 'get')
    {
        try {
            $uri = self::$idascApiAuth . $action;
            $client = new Client();
            $options = [
                'connect_timeout' => 5,
                'timeout' => 5,
            ];
            if ($method == 'get') {
                $options['query'] = $data;
            } else {
                $options['json'] = $data;
            }
            $response = $client->request($method, $uri, $options);
            $result = $response->getBody()->getContents();
            return json_decode($result, true);
        } catch (ClientException $e) {
            throw $e;
        }
    }
}
