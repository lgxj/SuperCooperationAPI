<?php
namespace App\Utils;

use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Faceid\V20180301\FaceidClient;
use TencentCloud\Faceid\V20180301\Models\DetectAuthRequest;
use TencentCloud\Faceid\V20180301\Models\GetDetectInfoRequest;

class TencentCloud
{

    protected $cred;
    protected $region;
    protected $endpoint;
    protected $httpProfile;
    protected $clientProfile;

    protected $RuleId;

    public function __construct()
    {
        $cred = new Credential(config('tencentcloud.secretId'), config('tencentcloud.secretKey'));
        $this->cred = $cred;

        $this->RuleId = config('tencentcloud.RuleId');
    }

    /**
     * 实名核身鉴权
     * @return false|string
     */
    public function detectAuth()
    {
        $client = $this->createFaceidClient();
        $req = new DetectAuthRequest();

        $params = ['RuleId' => $this->RuleId];
        $req->fromJsonString(json_encode($params, JSON_UNESCAPED_UNICODE));
        $resp = $client->DetectAuth($req);
        return $resp->toJsonString();
    }

    /**
     * 获取实名核身结果信息
     * @param $bizToken
     * @return false|string
     */
    public function getDetectInfo($bizToken)
    {
        $client = $this->createFaceidClient();
        $req = new GetDetectInfoRequest();

        $params = ['RuleId' => $this->RuleId];
        $req->fromJsonString(json_encode($params, JSON_UNESCAPED_UNICODE));
        $resp = $client->GetDetectInfo($req);
        return $resp->toJsonString();
    }

    protected function createHttpProfile()
    {
        $httpProfile = new HttpProfile();
        $httpProfile->setEndpoint($this->endpoint);
        $this->httpProfile = $httpProfile;
    }

    protected function createClientProfile()
    {
        $clientProfile = new ClientProfile();
        $clientProfile->setHttpProfile($this->httpProfile);
    }

    protected function createFaceidClient()
    {
        $this->endpoint = 'faceid.tencentcloudapi.com';
        $this->createHttpProfile();
        $this->createClientProfile();
        return new FaceidClient($this->cred, $this->region, $this->clientProfile);
    }
}
