<?php


namespace App\Services\User;


use App\Consts\ErrorCode\UserErrorCode;
use App\Consts\SmsConst;
use App\Exceptions\BusinessException;
use App\Models\User\UserBankCard;
use Illuminate\Support\Facades\Validator;

/**
 * 用户限行卡管理
 *
 * Class BankCardService
 * @package App\Services\User
 */
class BankCardService
{

    /**
     * @param array $userBank
     * @return array
     * @throws BusinessException
     */
    public function addUserBank(array $userBank)
    {
        $validate = Validator::make($userBank,[
            'user_id'=>'required|integer',
            'phone'=>'required',
            'code'=>'required',
            'real_name'=>'required',
            'card_no'=>'required|min:6|max:30',
            'bank_deposit_name'=>['required']
        ],[
            'phone.required' => '银行预留手机号不能为空',
            'code.required' => '手机发送的验证码不能为空',
            'real_name.required'=>"银行卡所绑定的姓名不能为空",
            'card_no.required'=>"银行卡号错误",
            'card_no.min'=>"银行卡号长度错误",
            'bank_deposit_name.required'=>'开户行不能为空',
        ]);
        if($validate->fails()){
            throw new BusinessException($validate->errors()->first(),UserErrorCode::BANK_VALIDATION_ERROR);
        }
        $userId = $userBank['user_id'];
        $userExistBank = UserBankCard::where(['user_id'=>$userId,'card_no'=>$userBank['card_no']])->first();
        if($userExistBank){
            throw new BusinessException("您已经添加过这张银行了",UserErrorCode::BANK_EXIST);
        }

        $codeService = new CodeService();
        $registerCode = $codeService->checkPhoneCode($userBank['phone'],$userBank['code'],SmsConst::BUSINESS_TYPE_BANK_PHONE);

        $userBankModel = new UserBankCard();
        $fields = $userBankModel->getTableColumns();
        foreach ($fields as $field) {
            if ($field == $userBankModel->getKeyName()) {
                continue;
            }
            if (isset($userBank[$field])) {
                $userBankModel->$field = $userBank[$field];
            }
        }
        $userBankModel->bank_name = getBankNameByCardNo($userBank['card_no']);
        $userBankModel->status = 1;
        $userBankModel->save();
        $registerCode->is_use = 1;
        $registerCode->save();
        return $userBankModel->toArray();
    }


    public function find(int $userId,int $id) : array
    {
        if($userId <= 0 || $id <= 0){
            return [];
        }
        $data = UserBankCard::where(['user_id'=>$userId,'bank_id'=>$id])->first();
        return $data ? $data->toArray() : [];
    }

    public function remove(int $userId ,int $id) : bool
    {
        if($userId <= 0 || $id <= 0){
            return false;
        }
        $flag = UserBankCard::where(['user_id'=>$userId,'bank_id'=>$id])->delete();
        return $flag > 0 ? true : false;
    }

    public function findAllByUid(int $userId) : array
    {
        if($userId <= 0){
            return [];
        }
        $data = UserBankCard::where(['user_id'=>$userId])->orderByDesc('bank_id')->get();
        return $data ? $data->toArray() : [];
    }
}
