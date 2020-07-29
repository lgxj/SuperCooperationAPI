<?php


namespace App\Utils;


use App\Consts\ErrorCode\RequestErrorCode;
use App\Exceptions\BusinessException;
use OSS\Core\OssException;
use OSS\OssClient;

/**
 * 阿里云OSS文件上传
 *
 * Class AliyunOss
 * @package App\Utils
 */
class AliyunOss
{

    protected $client;

    protected $bucket;

    public function __construct()
    {
        try {
            $this->client = new OssClient(config('oss.aliyun.access_key_id'), config('oss.aliyun.access_key_secret'), config('oss.aliyun.endpoint'));
        }catch (OssException $e){
            \Log::error("aliyun oss client init failed message:{$e->getMessage()}");
            Dingding::robot($e);
        }
        $this->bucket =  config('oss.aliyun.bucket');
    }

    /**
     * @param $bucket
     * @param bool $isThrow
     * @return null
     * @throws BusinessException
     */
    public function createBucket($bucket,$isThrow=false){
        try {echo config('oss.aliyun.access_key_id');
            return $this->client->createBucket($bucket);
        }catch (\Exception $e){
            \Log::error("aliyun oss client created bucket failed message:{$e->getMessage()}");
            if($isThrow) {
                throw new BusinessException($e->getMessage(),RequestErrorCode::ALIYUN_OSS_FAILED);
            }else{
                Dingding::robot($e);
            }
        }
        return null;
    }

    /**
     * @param $filename
     * @param $localPath
     * @param bool $isThrow
     * @return null
     * @throws BusinessException
     */
    public function uploadFile($filename,$localPath,$isThrow=false){
        try {
           return $this->client->putObject($this->bucket,$filename,$localPath);
        }catch (\Exception $e){
            \Log::error("aliyun oss client upload file failed message:{$e->getMessage()}");
            if($isThrow) {
                throw new BusinessException($e->getMessage(),RequestErrorCode::ALIYUN_OSS_FAILED);
            }else{
                Dingding::robot($e);
            }
        }
        return null;
    }

    /**
     * @param $filename
     * @param bool $isThrow
     * @return null
     * @throws BusinessException
     */
    public function deleteFile($filename,$isThrow=false){
        try {
           return $this->client->deleteObject($this->bucket,$filename);
        }catch (\Exception $e){
            \Log::error("aliyun oss client delete file failed message:{$e->getMessage()}");
            if($isThrow) {
                throw new BusinessException($e->getMessage(),RequestErrorCode::ALIYUN_OSS_FAILED);
            }else{
                Dingding::robot($e);
            }
        }
        return null;
    }

    public function downLoadFile($file,$isThrow=false){
        return $this->client->getObject($this->bucket,$file);
        return null;
    }

    public function getSignature()
    {
        $id= config('oss.aliyun.access_key_id');          // 请填写您的AccessKeyId。
        $key= config('oss.aliyun.access_key_secret');     // 请填写您的AccessKeySecret。
        // $host的格式为 bucketname.endpoint，请替换为您的真实信息。
        $host = 'http://' . config('oss.aliyun.bucket') . '.' . config('oss.aliyun.endpoint');
        $dir = 'user-upload/';          // 用户上传文件时指定的前缀。

        // $callbackUrl为上传回调服务器的URL，请将下面的IP和Port配置为您自己的真实URL信息。
//        $callbackUrl = 'http://88.88.88.88:8888/aliyun-oss-appserver-php/php/callback.php';
//        $callback_param = array('callbackUrl'=>$callbackUrl,
//            'callbackBody'=>'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
//            'callbackBodyType'=>"application/x-www-form-urlencoded");
//        $callback_string = json_encode($callback_param);
//        $base64_callback_body = base64_encode($callback_string);

        $now = time();
        $expire = 30;  //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问。
        $end = $now + $expire;
        $expiration = gmt_iso8601($end);


        //最大文件大小.用户可以自己设置
        $condition = array(0=>'content-length-range', 1=>0, 2=>104857600);
        $conditions[] = $condition;

        // 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
        $start = array(0=>'starts-with', 1=>'$key', 2=>$dir);
        $conditions[] = $start;

        $arr = array('expiration'=>$expiration,'conditions'=>$conditions);
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $key, true));

        $response = array();
        $response['accessid'] = $id;
        $response['host'] = $host;
        $response['policy'] = $base64_policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
//        $response['callback'] = $base64_callback_body;
        $response['dir'] = $dir;  // 这个参数是设置用户上传文件时指定的前缀。
        return $response;
    }
}
