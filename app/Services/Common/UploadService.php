<?php


namespace App\Services\Common;


use App\Consts\UploadFileConst;
use App\Exceptions\BusinessException;
use App\Services\ScService;
use App\Utils\AliyunOss;

/**
 * 文件上传服务
 *
 * Class UploadService
 * @package App\Services\Common
 */
class UploadService extends ScService
{

    /**
     * @param array $files
     * @param string $directory
     * @throws \App\Exceptions\BusinessException
     * @throws \OSS\Core\OssException
     * @return array
     */
    public function upload(array $files,$directory = '',$businessType = UploadFileConst::BUSINESS_TYPE_GENERAL){
        if(empty($files)){
            throw new BusinessException('请选择要上传的文件');
        }
        if(empty($directory)){
            throw new BusinessException('请输入要上传后的目录');
        }
        $oss = new AliyunOss();
        $data = [];
        foreach ($files as $field=>$value){
            if(is_array($value)){
                foreach ($value as $index=>$file){
                    list($fileName,$fileContent) = getFileContent($file,$directory,$businessType);
                    $response = $oss->uploadFile($fileName,$fileContent,true);
                    $data[$field][$index] = $response['info']['url'] ?? '';
                }
            }else{
                list($fileName,$fileContent) = getFileContent($value,$directory,$businessType);
                $response = $oss->uploadFile($fileName,$fileContent,true);
                $data[$field] = $response['info']['url'] ?? '';
            }
        }
        return $data;
    }

    /**
     * 保存文件（已读取文件内容）
     * @param $name
     * @param $content
     * @param string $directory
     * @return string
     * @throws \OSS\Core\OssException
     */
    public function uploadFileContent($name, $content, $directory = '')
    {
        $oss = new AliyunOss();
        $response = $oss->uploadFile($directory . '/' . $name, $content, true);
        return $response['info']['url'] ?? '';
    }

    /**
     * 保存Base64到OSS
     * @param $base64
     * @param $directory
     * @param $fileName
     * @return string
     * @throws \OSS\Core\OssException
     */
    public function uploadBase64($base64, $directory, $fileName)
    {
        if (!$base64) return '';
        $oss = new AliyunOss();
        $response = $oss->uploadFile($directory . $fileName, $base64,true);
        return $response['info']['url'] ?? '';
    }

    public function getSignature()
    {
        $oss = new AliyunOss();
        return $oss->getSignature();
    }

}
