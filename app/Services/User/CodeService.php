<?php


namespace App\Services\User;


use App\Consts\ErrorCode\SmsErrorCode;
use App\Consts\GlobalConst;
use App\Consts\SmsConst;
use App\Exceptions\BusinessException;
use App\Models\User\UserCodeVerify;
use App\Services\ScService;
use App\Utils\AliyunSms;
use Carbon\Carbon;

/**
 * 短信验证码服务层
 *
 * Class CodeService
 * @package App\Services\User
 */
class CodeService extends ScService
{

    protected $codeExpire = 15;

    protected $minute = 1;

    protected $dayLimit = 10;
    /**
     * @param int $userId 用户ID，可为0
     * @param string $account 验证账号
     * @param int $businessType 业务类型
     * @param int $codeType 验证类型 0表示手机验证 1表示邮箱验证
     * @param string $channel 验证通道
     * @return int
     * @throws BusinessException
     */
    public function sendCode($userId,$account,$businessType,$codeType,$channel){
        if(!SmsConst::inCodeType($codeType)){
            throw new BusinessException("短信验证类型错误",SmsErrorCode::SMS_CODE_TYPE_ERROR);
        }
        if(!SmsConst::inBusinessType($businessType)){
            throw new BusinessException("短信验证业务类型错误",SmsErrorCode::SMS_CODE_BUSINESS_TYPE_ERROR);
        }
        if(!SmsConst::inChannel($channel)){
            throw new BusinessException("短信通道错误",SmsErrorCode::SMS_CODE_CHANNEL_ERROR);
        }
        if(!($template = SmsConst::getTemplate($codeType,$businessType,$channel))){
            throw new BusinessException("短信通道错误",SmsErrorCode::SMS_CODE_TEMPLATE_ERROR);
        }
        if(empty($account)){
            throw new BusinessException("账号不能为空",SmsErrorCode::SMS_CODE_ACCOUNT_ERROR);
        }
        $userCodeVerifyModel = new UserCodeVerify();
        $smsData =  $userCodeVerifyModel->getLatestCodeByAccount($account,$businessType,$codeType);
        $carbon = Carbon::now();
        $code = mt_rand(1000,9999);
        if($smsData){
            $interval = $smsData->created_at->addMinutes($this->minute);
            if($interval > $carbon){
                throw new BusinessException("{$this->minute}分钟内不能再发验证消息",SmsErrorCode::SMS_CODE_TIME_MINUTE_LIMIT);
            }
            if($smsData->expired_at < $carbon){
                //code 过期处理
            }
        }
        if($userCodeVerifyModel->countByCount($account,$codeType) > $this->dayLimit){
            throw new BusinessException("您今天发送的验证达到上限",SmsErrorCode::SMS_CODE_TIME_TODAY_LIMIT);
        }
        $aliyunSms = new AliyunSms();
        $bizId = $aliyunSms->sendSms('',$account,$template,['code'=>$code]);
        if(empty($bizId)){
            throw new BusinessException("{$this->minute}发送验证码失败",SmsErrorCode::SMS_CODE_SEND_FAILED);
        }
        $expired = $carbon->addMinutes($this->codeExpire);
        $userCodeVerifyModel->user_id=$userId;
        $userCodeVerifyModel->verify_account = $account;
        $userCodeVerifyModel->business_type=$businessType;
        $userCodeVerifyModel->code_type=$codeType;
        $userCodeVerifyModel->code=$code;
        $userCodeVerifyModel->is_use= 0;
        $userCodeVerifyModel->channel= $channel;
        $userCodeVerifyModel->channel_bizid=$bizId;
        $userCodeVerifyModel->expired_at=$expired;
        $userCodeVerifyModel->save();
        return $userCodeVerifyModel->code_id;

    }

    public function getLatestCode($account,$businessType,$codeType){
        $userCodeVerifyModel = new UserCodeVerify();
        return  $userCodeVerifyModel->getLatestCodeByAccount($account,$businessType,$codeType);
    }

    /**
     *
     * @param string $phone 手机号
     * @param int $code 验证码
     * @param int $businessType 业务类型
     * @throws BusinessException
     * @return UserCodeVerify
     */
    public function checkPhoneCode($phone,$code,$businessType){
        $registerCode = $this->getLatestCode($phone,$businessType,SmsConst::CODE_TYPE_PHONE);
        if(empty($registerCode)){
            throw new BusinessException("手机验证码不存在",SmsErrorCode::SMS_CODE_VERIFY_NOT_EXIST);
        }
        if($registerCode->code != $code){
            throw new BusinessException("验证码错误",SmsErrorCode::SMS_CODE_VERIFY_ERROR);
        }
        $now = Carbon::now();
        if($now > $registerCode->expired_at){
            throw new BusinessException("验证码过期，请重新获取验证码",SmsErrorCode::SMS_CODE_VERIFY_EXPIRED);
        }
        if($registerCode->is_use != 0){
            throw new BusinessException("验证码已经使用，请重新获取验证码",SmsErrorCode::SMS_CODE_VERIFY_USED);
        }
        return $registerCode;
    }
}
