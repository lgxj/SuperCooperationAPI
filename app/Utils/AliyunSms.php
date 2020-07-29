<?php


namespace App\Utils;


use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class AliyunSms
{
    protected $regionId = '';
    public function __construct()
    {
        $this->regionId = config('aliyun.sms.region_id');

        AlibabaCloud::accessKeyClient(config('aliyun.sms.access_key_id'), config('aliyun.sms.access_secret'))
            ->regionId($this->regionId) // replace regionId as you need
            ->asDefaultClient();
    }

    public function sendSms($outId,$phone,$template,array $param) : string
    {
        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')->version('2017-05-25')->action('SendSms')->method('POST')->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => $this->regionId,
                        'PhoneNumbers' => $phone,
                        'SignName' => config('aliyun.sms.sign_name'),
                        'TemplateCode' => $template,
                        'TemplateParam' => json_encode($param),
                        'OutId' => $outId,
                    ],
                ])
                ->request();
            $data = $result->toArray();
            if(isset($data['BizId']) && $data['Code'] == 'OK'){
                return $data['BizId'];
            }
            \Log::error("aliyun sms fail phone:{$phone} message:{$data['Message']}");
        } catch (ClientException $e) {
            \Log::error("aliyun sms fail phone:{$phone} message:{$e->getErrorMessage()}");
        } catch (ServerException $e) {
            \Log::error("aliyun sms fail phone:{$phone} message:{$e->getErrorMessage()}");
        }
        return '';
    }
}
