<?php


namespace App\Models\User;


use App\Consts\UserConst;

class UserGrantLogin extends BaseUser
{

    protected $table = 'user_grant_login';

    protected $primaryKey = 'grant_login_id';

    public function findByGrantType($grantType,$grantIdentify)
    {
        return $this->where(['grant_login_identify'=>$grantIdentify,'grant_login_type'=>$grantType])->first();
    }

    public function findByUserPhone($userId){
        return $this->where(['user_id'=>$userId,'grant_login_type'=>UserConst::GRANT_LOGIN_TYPE_PHONE])->first();
    }


    public function findByUserGrantType($userId, $grantType)
    {
        return $this->where(['user_id'=>$userId,'grant_login_type'=>$grantType])->first();
    }

    public function findByUserGrantId($userId, $grantId)
    {
        return $this->where(['user_id'=>$userId,'grant_login_id'=>$grantId])->first();
    }
}
