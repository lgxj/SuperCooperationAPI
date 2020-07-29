<?php


namespace App\Models\User;


use App\Models\ScModel;

class UserCodeVerify extends BaseUser
{
    protected $table = 'user_code_verify';

    protected $primaryKey = 'code_id';

   // protected $fillable = ['user_id'];


    public function getLatestCodeByAccount($count,$businessType,$codeType){
        $data = $this->where(['verify_account'=>$count,'business_type'=>$businessType,'code_type'=>$codeType,'is_use'=>0])
             ->orderByDesc('created_at')
              ->first();
        return $data;
    }

    public function countByCount($count,$codeType){
        $time = time();
        $dateStart = strtotime(date('Y-m-d 0:0:0',$time));
        $dateEnd = strtotime(date('Y-m-d 23:59:59',$time));
        $data = $this->where(['verify_account'=>$count,'code_type'=>$codeType])->whereBetween('created_at',[$dateStart,$dateEnd])
            ->orderByDesc('created_at')
            ->count();
        return $data;
    }
}
