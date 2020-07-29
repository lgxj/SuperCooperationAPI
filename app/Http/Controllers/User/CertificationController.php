<?php
namespace App\Http\Controllers\User;

use App\Consts\UserConst;
use App\Http\Controllers\Controller;
use App\Services\User\CertificationService;
use Illuminate\Http\Request;

class CertificationController extends Controller
{

    protected $service;

    public function __construct(CertificationService $service)
    {
        $this->service = $service;
    }

    /**
     * ocr初始化
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function ocrInit()
    {
        $userId = $this->getUserId();
        $result = $this->service->ocrInit($userId);
        return success($result);
    }

    /**
     * 获取OCR识别结果
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \OSS\Core\OssException
     */
    public function getOcrResult(Request $request)
    {
        $orderNo = $request->input('orderNo');
        $result = $this->service->getOcrResult($this->getUserId(), $orderNo);
        return success($result);
    }

    /**
     * 手动录入身份信息
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function saveInfo(Request $request)
    {
        $name = $request->input('name');
        $idcard = $request->input('idcard');
        $this->service->saveInfo($this->getUserId(), $name, $idcard);
        return success();
    }

    /**
     * 人脸核身初始化
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function verifyInit()
    {
        $userId = $this->getUserId();
        $result = $this->service->verifyInit($userId);
        return success($result);
    }

    /**
     * 人脸核身结果查询
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \OSS\Core\OssException
     */
    public function getVerifyResult(Request $request)
    {
        $orderNo = $request->input('orderNo');
        $businessType = $request->input('business_type', '1');
        $businessNo = $request->input('business_no', '');
        $result = $this->service->getVerifyResult($this->getUserId(), $orderNo, $businessType, $businessNo);
        return success($result);
    }

    /**
     * 获取认证信息
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInfo(Request $request)
    {
        $businessType = $request->input('business_type',UserConst::FACE_AUTH_TYPE_HELPER);
        $businessNo = $request->input('business_no','');
        $result = $this->service->getInfo($this->getUserId(),$businessType,$businessNo);
        return success($result);
    }
}
