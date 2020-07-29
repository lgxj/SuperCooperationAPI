<?php


namespace App\Models\User;


use Illuminate\Database\Eloquent\SoftDeletes;

class UserSkillCertify extends BaseUser
{
    use SoftDeletes;

    protected $table = 'user_skill_certify';

    protected $primaryKey = 'certify_id';

    public function getByCardNo($userId,$cardType,$cardNo){
        return $this->where(['user_id'=>$userId,'card_type'=>$cardType,'card_no'=>$cardNo])->first();
    }
}
