<?php
namespace App\Services\User;

use App\Consts\DBConnection;
use App\Consts\ErrorCode\UserErrorCode;
use App\Consts\RealNameAuthConst;
use App\Consts\UserConst;
use App\Exceptions\BusinessException;
use App\Models\User\User;
use App\Models\User\UserFace;
use App\Models\User\UserRealName;
use App\Services\Common\UploadService;
use App\Services\ScService;
use App\Utils\RealNameAuth;
use App\Utils\UniqueNo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * 腾讯云实名认证服务层
 *
 * @package App\Services\User
 */
class CertificationService extends ScService
{
    /**
     * ocr初始化
     * @param $userId
     * @return array
     * @throws BusinessException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function ocrInit($userId)
    {
        $result = (new RealNameAuth)->ocrInit($userId);
        $result['orderNo'] = UniqueNo::buildRealNameAuthNo($userId);
        $result['ip'] = request()->ip();
        /**
         * OCR识别模式。(注意值是字符串！！！)
         * 0：标准模式，SDK 调起成功后，先进入拍摄准备页面，待正反两面识别完成之后，将本次识别结果返回到第三方 App。；
         * 1：人像面识别模式，SDK 调起成功后，直接进入拍摄识别页面，识别身份证人像面，识别完成之后，将本次识别结果返回第三方 App。
         * 2：国徽面识别模式，SDK 调起成功后，直接进入拍摄识别页面，识别身份证国徽面，识别完成之后，将本次识别结果返回第三方 App。
         * 3：银行卡识别模型。
         */
        $result['SDKType'] = '0';
        return $result;
    }

    /**
     * 获取OCR识别结果
     * @param $userId
     * @param $orderNo
     * @return bool|mixed
     * @throws BusinessException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \OSS\Core\OssException
     */
    public function getOcrResult($userId, $orderNo)
    {
        if (!$orderNo) {
            throw new BusinessException('请求参数错误',UserErrorCode::CERTIFICATION_OKR_PARAM_ERROR);
        }

        $result = (new RealNameAuth)->getOcrResult($orderNo);
        if (!$result) {
            throw new BusinessException('身份证识别失败',UserErrorCode::CERTIFICATION_OKR_VERIFY_FAILED);
        }

        $result = $result['result'];

        // Base64转图片存储
        !empty($result['frontCrop']) && ($result['frontPhoto'] = (new UploadService)->uploadFileContent($userId . '-front.png', base64_decode($result['frontCrop']), 'realNameAuth'));
        !empty($result['backCrop']) && ($result['backPhoto'] = (new UploadService)->uploadFileContent($userId . '-back.png', base64_decode($result['backCrop']), 'realNameAuth'));
        !empty($result['headPhoto']) && ($result['headPhoto'] = (new UploadService)->uploadFileContent($userId . '-head.png', base64_decode($result['headPhoto']), 'realNameAuth'));

        unset($result['frontCrop'], $result['backCrop'], $result['headCrop']);

        $result['input_type'] = UserRealName::INPUT_TYPE_OCR;
        $result['ocr_order_no'] = $orderNo;
        $result['user_id'] = $userId;
        (new UserRealName)->saveInfo($userId, $result);

        return $result;
    }

    /**
     * 手动输入姓名&身份证号
     * @param $userId
     * @param $name
     * @param $idcard
     * @return bool
     * @throws BusinessException
     */
    public function saveInfo($userId, $name, $idcard)
    {
        $data = [
            'user_id' => $userId,
            'name' => $name,
            'idcard' => $idcard,
            'input_type' => UserRealName::INPUT_TYPE_MANUAL,
        ];

        $validate = \Validator::make($data, [
            'name' => 'required',
            'idcard' => 'required'
        ], [
            'name.required' => '姓名不能为空',
            'idcard.unique' => '身份证号不能为空'
        ]);
        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first(),UserErrorCode::CERTIFICATION_OKR_VALIDATION_ERROR);
        }

        if ((new UserRealName)->saveInfo($userId, $data)) {
            return true;
        } else {
            throw new BusinessException('保存信息失败',UserErrorCode::CERTIFICATION_OKR_VERIFY_FAILED);
        }
    }

    /**
     * 人脸核身初始化
     * @param $userId
     * @return array
     * @throws BusinessException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function verifyInit($userId)
    {
        $realName = (new UserRealName)->where('user_id', $userId)->first();

        if (!$realName) {
            throw new BusinessException('没有您的身份信息，请先录入',UserErrorCode::CERTIFICATION_OKR_INFO_NOT_EXIST);
        }

        $orderNo = UniqueNo::buildRealNameAuthNo($userId, RealNameAuthConst::TYPE_FACE_VERIFY);
        $result = (new RealNameAuth)->verifyInit($userId, $realName['name'], $realName['idcard'], $orderNo);
        $result['ip'] = request()->ip();

        $result['showSuccessPage'] = true;  // 是否展示成功页面
        $result['showFailurePage'] = true;  // 是否展示失败页面
        $result['recordVideo'] = false;     // 是否录制视频
        $result['playVoice'] = true;        // 是否播放语音提示
        $result['detectCloseEyes'] = true;  // 是否检测用户闭眼
        $result['theme'] = '0';             // sdk皮肤设置。0：黑色；1：白色（注意值是字符串！！！）
        $result['faceType'] = '0';          // 认证类型。0：动作活体；1：光线活体（注意值是字符串！！！）
        $result['compareType'] = '0';       // 对比类型。0：身份证对比；1：自带对比源；2：仅活体检测（注意值是字符串！！！）

        return $result;
    }

    /**
     * 人脸核身结果
     * @param $userId
     * @param $orderNo
     * @param int $businessType 业务类型。1：帮手认证；2：接单扫脸
     * @param string $businessNo 业务相关编号，如接单扫脸时的订单号
     * @return array
     * @throws BusinessException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \OSS\Core\OssException
     */
    public function getVerifyResult($userId, $orderNo, $businessType, $businessNo)
    {
        $realName = (new UserRealName)->where('user_id', $userId)->first();
        if (!$realName) {
            throw new BusinessException('未找到认证信息',UserErrorCode::CERTIFICATION_OKR_INFO_NOT_EXIST);
        }

        $result = (new RealNameAuth)->getVerifyResult($orderNo);
        if (!$result || $result['code']) {
            throw new BusinessException('认证失败',UserErrorCode::CERTIFICATION_OKR_VERIFY_FAILED);
        }

        $result = $result['result'];

        // 图片存储
        !empty($result['photo']) && ($result['photo'] = (new UploadService)->uploadFileContent($userId . '-photo.png', base64_decode($result['photo']), 'realNameAuth'));

        $data = [
            'user_id' => $userId,
            'liveRate' => $result['liveRate'],
            'similarity' => $result['similarity'],
            'occurredTime' => $result['occurredTime'],
            'photo' => $result['photo'],
            'business_type' => $businessType,
            'business_no' => $businessNo,
            'face_order_no' => $orderNo
        ];
        $connection = DBConnection::getUserConnection();
        try {
            // 修改数据记录
            (new UserFace)->saveInfo($userId, $businessType, $businessNo, $data);
            if ($businessType == UserConst::FACE_AUTH_TYPE_HELPER) {
                // 更新用户认证状态
                (new User)->where('user_id', $userId)->update(['is_certification' => 1]);
            }
            $connection->commit();
            return $data;
        } catch (\Exception $e) {
            $connection->rollBack();
            Log::error('保存认证信息失败。message: ' . $e->getMessage() . '。info:' . json_encode($data));
            throw new BusinessException('保存认证信息失败',$e->getCode());
        }
    }

    /**
     * 获取认证信息
     * @param int $userId
     * @param int $businessType
     * @param int $businessNo
     * @return array
     */
    public function getInfo($userId,$businessType = UserConst::FACE_AUTH_TYPE_HELPER,$businessNo = '')
    {
        $realName = (new UserRealName)->where('user_id', $userId)->first();
        if (!$realName) {
            return [
                'status' => 0
            ];
        } else {
            $data = $realName->toArray();
            $status = 1;
            $face = (new UserFace)->where('user_id', $userId)->where(['business_type'=> $businessType,'business_no'=>$businessNo])->first();
            if ($face) {
                $status = 2;
                $data = array_merge($data, $face->toArray());
            }
            $data['status'] = $status;
            return $data;
        }
    }

    /**
     * 判断指定业务下是否已扫脸
     * @param $userId
     * @param $businessNo
     * @param int $businessType
     * @return Builder|Model|object|null
     */
    public function isFaceAuth($userId, $businessNo, $businessType = UserConst::FACE_AUTH_TYPE_RECEIVE)
    {
        return (new UserFace)->where('user_id', $userId)->where('business_type', $businessType)->where('business_no', $businessNo)->first();
    }

    /**
     * 判断指定业务下是否已扫脸
     * @param $userIds
     * @param $businessNo
     * @param int $businessType
     * @return Builder|Model|object|null
     */
    public function getFaceAuthByUserIds(array $userIds, $businessNo, $businessType = UserConst::FACE_AUTH_TYPE_RECEIVE)
    {
        if(empty($userIds)){
            return null;
        }
        return (new UserFace)->whereIn('user_id', $userIds)->where(['business_type'=> $businessType,'business_no'=>$businessNo])->get()->keyBy('user_id');
    }

}
