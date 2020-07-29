<?php


namespace App\Services\User;


use App\Consts\ErrorCode\UserErrorCode;
use App\Exceptions\BusinessException;
use App\Models\User\UserBlack;
use App\Services\ScService;

/**
 * 用户黑名单功能
 *
 * Class BlackService
 * @package App\Services\User
 */
class BlackService extends ScService
{
    /**
     * @param int $userId
     * @param int $blackUserId
     * @throws BusinessException
     * @return boolean
     */
    public function add($userId,$blackUserId){
        if($userId <=0 || $blackUserId <= 0){
            throw new BusinessException("参数错误",UserErrorCode::BLACK_PARAM_ERROR);
        }
        if($userId == $blackUserId){
            throw new BusinessException("您不能添加自己为黑名单",UserErrorCode::BLACK_SAVE_NOT_SELF);
        }
        if($this->get($userId,$blackUserId)){
            return true;
        }
        $userModel = $this->getModel();
        $userModel->user_id = $userId;
        $userModel->black_user_id = $blackUserId;
        $userModel->save();
        return true;
    }

    /**
     * @param int $userId
     * @param int $blackUserId
     * @return bool
     * @throws BusinessException
     */
    public function remove($userId,$blackUserId){
        if($userId <=0 || $blackUserId <= 0){
            throw new BusinessException("参数错误",UserErrorCode::BLACK_PARAM_ERROR);
        }
        $this->getModel()->where(['user_id'=>$userId,'black_user_id'=>$blackUserId])->delete();
        return true;
    }

    /**
     * @param int $userId
     * @param int $blackUserId
     * @return array
     */
    public function get($userId,$blackUserId){
        if($userId <=0 || $blackUserId <= 0){
            return [];
        }
        $black = $this->getModel()->where(['user_id'=>$userId,'black_user_id'=>$blackUserId])->first();
        return $black ? $black->toArray() : [];
    }

    public function getModel(){
        return new UserBlack();
    }

}
