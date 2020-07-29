<?php


namespace App\Utils;


use App\Exceptions\BusinessException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use TencentCloud\Common\Credential;

class TelephoneProtect
{
    protected $apiAuth = 'https://npp.tencentcloudapi.com/';
    protected $version = '2019-08-23';
    protected $region = '';
    protected $appId = '';
    protected $secretId = '';
    protected $secretKey = '';
    public function __construct()
    {
        $this->secretId = config('tencentcloud.secretId');
        $this->secretKey = config('tencentcloud.secretKey');
    }

    /**
     * @param $userId
     * @param $bizType
     * @param $orderId
     * @param $srcTel
     * @param $dstTel
     * @param int $isRecord
     * @param int $maxAllowTime
     * @return mixed
     * @throws BusinessException
     */
    public function createCallBack($userId,$bizType,$orderId,$srcTel,$dstTel,$isRecord = 0,$maxAllowTime = 10){
        $data = $this->buildCommonParam();
        $data['Action'] = 'CreateCallBack';
        $data['StatusFlag'] = 16191;
        $data['StatusUrl'] = env('APP_URL').'/callback/phone/notify.status';
        $data['RecordUrl'] = env('APP_URL').'/callback/phone/notify.record';
        $data['HangupUrl'] = env('APP_URL').'/callback/phone/notify.hangup';
        $data['Srt'] = $srcTel;
        $data['Dst'] = $dstTel;
        $data['Record'] = $isRecord;
        $data['MaxAllowTime'] = $maxAllowTime;
        $data['BizId'] = UniqueNo::buildTelContactTaskNo($userId,$bizType);
        $data['OrderId'] = $orderId;
        $response = $this->request($data);
        $data['success'] = true;
        $data['CallId'] = $response['CallId'];
        $data['RequestId'] = $response['RequestId'];
        return $data;
    }


    /**
     * @param $callId
     * @return mixed
     */
    public function cancelCallBack($callId){
        $data['Action'] = 'DeleteCallBack';
        $data['CancelFlag'] = 0;
        $data['CallId'] = $callId;
        $response = $this->request($data);
        $data['CallId'] = $response['CallId'];
        $data['RequestId'] = $response['RequestId'];
        return $data;
    }

    /**
     * @param $callId
     * @param $srcTel
     * @param $dstTel
     * @return mixed
     */
    public function describeCallBackStatus($callId,$srcTel,$dstTel){
        $data['Action'] = 'DescribeCallBackStatus';
        $data['CallId'] = $callId;
        $data['Srt'] = $srcTel;
        $data['Dst'] = $dstTel;
        $response = $this->request($data);
        $data['CallId'] = $response['CallId'];
        $data['RequestId'] = $response['RequestId'];
        return $data;
    }

    /**
     * @param $data
     * @return mixed
     */
    protected function request($data)
    {
        try {
            $uri = $this->apiAuth;
            $data['Signature'] = $this->sign($data,true);
            $client = new Client();
            $options = [
                'connect_timeout' => 5,
                'timeout' => 5,
                'verify'=>false,
                'form_params'=> $data
            ];

            $options['headers'] = [
                'Content-Type'     => 'application/x-www-form-urlencoded'
            ];
            $response = $client->post($uri, $options);
            $result = $response->getBody()->getContents();
            return json_decode($result, true);
        } catch (ClientException $e) {
            throw $e;
        }
    }

    protected  function sign(array $paraMap,  $urlEncode = false)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if (null != $v && "null" != $v) {
                if ($urlEncode) {
                    $v = urlencode($v);
                }

                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        $sign = base64_encode(hash_hmac('sha1', $reqPar, $this->secretKey, true));
        return $sign;
    }

    protected function buildCommonParam(){
        $data['Timestamp'] = time();
        $data['Nonce'] = rand(10000,99999);
        $data['Region'] = $this->region;
        $data['SecretId'] = $this->secretId;
        $data['Version'] = $this->version;
        $data['BizAppId'] = $this->appId;
        return $data;
    }
}
