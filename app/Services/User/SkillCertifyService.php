<?php


namespace App\Services\User;


use App\Consts\CertificateConst;
use App\Consts\ErrorCode\UserErrorCode;
use App\Consts\UploadFileConst;
use App\Exceptions\BusinessException;
use App\Models\User\UserSkillCertify;
use App\Utils\AliyunOss;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * 技能与证书管理服务层
 *
 * Class SkillCertifyService
 * @package App\Services\User
 */
class SkillCertifyService
{
    protected $directory = 'skill';

    const MAX_SKILL = 10;

    /**
     * @param array $data
     * @param array $files
     * @return BusinessException|array
     * @throws BusinessException
     * @throws \OSS\Core\OssException
     */
    public function addCertificate(array $data, array $files = []){
        $cardTypeList = CertificateConst::licenseList();
        $validate = Validator::make($data,[
            'user_id'=>'required|integer',
            'order_category' => 'required|integer',
            'cart_type'=>[Rule::in(array_keys($cardTypeList))],
            'real_name'=>'required',
            'card_no'=>'required',
            'original_url'=>'required'
        ],[
            'user_id.required' => '用户标识不能为空',
            'cart_type.required' => '证件类型不能为空',
            'real_name.required' => '证件所属姓名不能为空',
            'card_no.required'=>"证件号码不能为空",
            'original_url.required'=>"证件照片不能为空",
            'order_category' => "证件服务类型不能为空",
        ]);
        if($validate->fails()){
            throw new BusinessException($validate->errors()->first(),UserErrorCode::SKILL_VALIDATION_ERROR);
        }

        $total = $this->total($data['user_id']);
        if($total >= self::MAX_SKILL){
            return new BusinessException("您最多只能添加10个证书",UserErrorCode::SKILL_NUM_MAX_LIMIT);
        }
        $skillCertify = new UserSkillCertify();
        if($skillCertify->getByCardNo($data['user_id'],$data['card_type'],$data['card_no'])){
            throw new BusinessException("此证件类型已添加过了",UserErrorCode::SKILL_EXIST);
        }
        foreach ($files as $key=>$file){
            $data[$key] = getFileContent($file,$this->directory,UploadFileConst::BUSINESS_TYPE_GENERAL);
        }
        $oss = new AliyunOss();
        if(!empty($data['original_url'])){
            list($fileName,$fileContent) = $data['original_url'];
            $response = $oss->uploadFile($fileName,$fileContent,true);
            $data['original_url'] = $response['info']['url'] ?? '';
        }
        if(!empty($data['copy_url'])){
            list($fileName,$fileContent) = $data['copy_url'];
            $response = $oss->uploadFile($fileName,$fileContent,true);
            $data['copy_url'] = $response['info']['url'] ?? '';
        }

        $fields = $skillCertify->getTableColumns();
        foreach ($fields as $field) {
            if ($field == $skillCertify->getKeyName()) {
                continue;
            }
            if (isset($data[$field])) {
                $skillCertify->$field = $data[$field];
            }
        }
        $skillCertify->status = 1;
        $skillCertify->save();
        return $skillCertify->toArray();
    }

    /**
     * @param array $data
     * @param array $files
     * @return array
     * @throws BusinessException
     * @throws \OSS\Core\OssException
     */
    public function updateCertificate(array $data,array $files = []){
        $cardTypeList = CertificateConst::licenseList();
        $validate = Validator::make($data,[
            'certify_id'=> 'required|integer',
            'user_id'=>'required|integer',
            'order_category' => 'required|integer',
            'cart_type'=>[Rule::in(array_keys($cardTypeList))],
            'real_name'=>'required',
            'card_no'=>'required'
        ],[
            'user_id.required' => '用户标识不能为空',
            'cart_type.required' => '证件类型不能为空',
            'real_name.required' => '证件所属姓名不能为空',
            'card_no.required'=>"证件号码不能为空",
            'original_url.required'=>"证件照片不能为空",
            'certify_id.required' => '证件标识不能为空',
            'order_category' => "证件服务类型不能为空",

        ]);
        if($validate->fails()){
            throw new BusinessException($validate->errors()->first());
        }
        $skillCertify =  UserSkillCertify::find($data['certify_id']);
        if(!$skillCertify){
            throw new BusinessException("此证件类型不存在",UserErrorCode::SKILL_VALIDATION_ERROR);
        }
        foreach ($files as $key=>$file){
            $data[$key] = getFileContent($file,$this->directory,UploadFileConst::BUSINESS_TYPE_GENERAL);
        }
        $oss = new AliyunOss();
        if(!empty($files['original_url'])){
            list($fileName,$fileContent) = $data['original_url'];
            $response = $oss->uploadFile($fileName,$fileContent,true);
            $data['original_url'] = $response['info']['url'] ?? '';
        }
        if(!empty($files['copy_url'])){
            list($fileName,$fileContent) = $data['copy_url'];
            $response = $oss->uploadFile($fileName,$fileContent,true);
            $data['copy_url'] = $response['info']['url'] ?? '';
        }
        $fields = $skillCertify->getTableColumns();
        foreach ($fields as $field) {
            if ($field == $skillCertify->getKeyName()) {
                continue;
            }
            if (isset($data[$field])) {
                $skillCertify->$field = $data[$field];
            }
        }
        $skillCertify->save();
        return $skillCertify->toArray();
    }

    public function findAllByUid(int $userId) : array
    {
        if($userId <= 0){
            return [];
        }
        $licenseList = $this->getLicenseList();
        $data = UserSkillCertify::where(['user_id'=>$userId])->orderByDesc('certify_id')->get();
        $data->map(function ($row) use($licenseList){
            $row['type_desc'] = $licenseList[$row['card_type']] ?? '';
            return $row;
        });
        return $data ? $data->toArray() : [];
    }

    public function find(int $userId,int $id) : array
    {
        if($userId <= 0 || $id <= 0){
            return [];
        }
        $data = UserSkillCertify::where(['user_id'=>$userId,'certify_id'=>$id])->first();
        return $data ? $data->toArray() : [];
    }

    public function remove(int $userId ,int $id) : bool
    {
        if($userId <= 0 || $id <= 0){
            return false;
        }
        $flag = UserSkillCertify::where(['user_id'=>$userId,'certify_id'=>$id])->delete();
        return $flag > 0 ? true : false;
    }


    public function total(int $userId) : int
    {
        return UserSkillCertify::where(['user_id'=>$userId])->count();
    }

    public function getLicenseList(){
        return CertificateConst::licenseList();
    }
}
