<?php


namespace App\Services\User;


use App\Bridges\Message\PushBridge;
use App\Bridges\Trade\Category\TaskCategoryManagerBridge;
use App\Consts\CertificateConst;
use App\Consts\DBConnection;
use App\Consts\ErrorCode\UserErrorCode;
use App\Consts\SmsConst;
use App\Consts\UserConst;
use App\Exceptions\BusinessException;
use App\Models\Trade\Order\Category;
use App\Models\User\ChangeLog;
use App\Models\User\User;
use App\Models\User\UserAcceptConfig;
use App\Models\User\UserAddress;
use App\Models\User\UserAddressPosition;
use App\Models\User\UserBlack;
use App\Models\User\UserCodeVerify;
use App\Models\User\UserFace;
use App\Models\User\UserGrantLogin;
use App\Models\User\UserLoginRecord;
use App\Models\User\UserRealName;
use App\Models\User\UserSkillCertify;
use App\Services\Message\PushService;
use App\Services\ScService;
use App\Services\Trade\Category\TaskCategoryManagerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * 用户注册与登录服务层
 *
 * Class UserService
 * @package App\Services\User
 */
class UserService extends ScService
{
    /**
     * @param $phone
     * @param $code
     * @param $password
     * @param $rePassword
     * @param int $thirdPartyUserId
     * @return array
     * @throws BusinessException
     */
    public function registerByPhone($phone,$code,$password,$rePassword,$thirdPartyUserId = 0)
    {
        $code = trim($code);
        $phone = trim($phone);
        $data = [
            'phone'=>$phone,
            'code'=>$code,
            'password'=>$password,
            'password_confirmation'=>$rePassword
        ];
        $validate = Validator::make($data,[
            'phone'=>'required',
            'code'=>'required',
            'password'=>'required|min:6|max:15',
            'password_confirmation'=>['required',"same:password"]
        ],[
            'phone.required' => '手机号不能为空',
            'code.required' => '验证码不能为空',
            'password.required'=>"密码不能为空",
            'password_confirmation.required'=>"确认密码不能为空",
            'password_confirmation.same'=>'密码与确认密码不匹配',
            'password.min' => "密码长度最小是6位"
        ]);
        if($validate->fails()){
            throw new BusinessException($validate->errors()->first(),UserErrorCode::REGISTER_VALIDATION_ERROR);
        }
        $grantModel = new UserGrantLogin();
        $registerUser = $grantModel->findByGrantType(UserConst::GRANT_LOGIN_TYPE_PHONE,$phone);
        if($registerUser){
            throw new BusinessException("该手机号已经注册过了！",UserErrorCode::REGISTER_PHONE_EXIST);
        }

        $registerCode = $this->checkPhoneCode($phone,$code,SmsConst::BUSINESS_TYPE_REGISTER);
        $salt = quick_random(6);
        $password = $this->buildPassword($password,$salt);
        $userModel = null;
        if($thirdPartyUserId > 0){
            $userModel =  User::find($thirdPartyUserId);
            $user_id = $thirdPartyUserId;
        }
        $connection = DBConnection::getUserConnection();
        try {
            $connection->beginTransaction();
            if(empty($userModel)) {
                $userModel = new User();
                $userModel->user_name = encryptPhone($phone);
                $userModel->user_status = 1;
                $userModel->register_salt = $salt;
                $userModel->register_type = UserConst::GRANT_LOGIN_TYPE_PHONE;
                $userModel->register_ip = ip2long(request()->ip());
                $userModel->user_avatar = '';
                $userModel->save();
                $user_id = $userModel->user_id;
                if ($user_id <= 0) {
                    throw new BusinessException("注册失败",UserErrorCode::REGISTER_FAILED);
                }
            }
            $grantModel->user_id =$user_id;
            $grantModel->grant_login_type =UserConst::GRANT_LOGIN_TYPE_PHONE;
            $grantModel->grant_login_identify = $phone;
            $grantModel->grant_login_credential = $password;
            $grantModel->grant_login_ip = ip2long(request()->ip());
            $grantModel->grant_status = 1;
            $grantModel->save();
            $registerCode->is_use = 1;
            $registerCode->save();
            //$this->saveDefaultConfig($user_id);
            $connection->commit();
            return $this->setLogin($userModel,$grantModel,$phone);
        }catch (\Exception $e){
            $connection->rollBack();
            \Log::error("user register failed phone:{$phone} message:{$e->getMessage()}");
            throw new BusinessException($e->getMessage(),$e->getCode());
        }
    }

    /**
     * 仅简单添加用户
     * @param $userName
     * @param $avatar
     * @param $type
     * @return mixed
     * @throws BusinessException
     */
    public function addUser($userName, $avatar, $type = UserConst::GRANT_LOGIN_TYPE_ADMIN)
    {
        $salt = $salt = quick_random(6);
        $userModel = new User();
        $userModel->user_name = $userName;
        $userModel->user_status = 1;
        $userModel->register_salt = $salt;
        $userModel->register_type = $type;
        $userModel->register_ip = ip2long(request()->ip());
        $userModel->user_avatar = $avatar;
        $userModel->save();
        $user_id = $userModel->user_id;
        if ($user_id <= 0) {
            throw new BusinessException("注册失败",UserErrorCode::REGISTER_FAILED);
        }
        //$this->saveDefaultConfig($user_id);
        return $user_id;
    }

    /**
     * 更新用户基础信息
     *
     * @param $userId
     * @param $userName
     * @param $avatar
     * @param float $employerLevel
     * @param float $helperLevel
     * @return array
     * @throws BusinessException
     */
    public function updateUserBaseInfo($userId, $userName, $avatar,float $employerLevel = null,float  $helperLevel = null)
    {
        $user = (new User)->find($userId);
        if (!$user) {
            throw  new BusinessException('用户不存在',UserErrorCode::INFO_NOT_EXIST);
        }
        if($userName) {
            $user->user_name = $userName;
        }
        if($avatar) {
            $user->user_avatar = $avatar;
        }
        if(!is_null($employerLevel)){
            $user->employer_level = $employerLevel;
        }
        if(!is_null($helperLevel)){
            $user->helper_level = $helperLevel;
        }
        $user->save();
        return $user->toArray();
    }

    public function updateUserAccept($userId,$accept = 0)
    {
        $user = (new User)->find($userId);
        if (!$user) {
            throw  new BusinessException('用户不存在',UserErrorCode::INFO_NOT_EXIST);
        }
        $user->is_accept_order = (int)$accept;
        $user->save();
        return $user->toArray();
    }

    public function updateUserStatus($userId,$status = 0)
    {
        $user = (new User)->find($userId);
        if (!$user) {
            throw  new BusinessException('用户不存在',UserErrorCode::INFO_NOT_EXIST);
        }
        $user->user_status = (int)$status;
        $user->save();
        return $user->toArray();
    }

    /**
     * @param $phone
     * @param $password
     * @return array
     * @throws BusinessException
     */
    public function loginWithPhonePass($phone,$password)
    {
        $password = trim($password);
        $phone = trim($phone);
        $grantModel = new UserGrantLogin();
        /** @var UserGrantLogin $userGrant */
        $userGrant = $grantModel->findByGrantType(UserConst::GRANT_LOGIN_TYPE_PHONE,$phone);
        if(empty($userGrant)){
            throw new BusinessException("您还没有注册，请先注册",UserErrorCode::INFO_PHONE_NOT_EXIST);
        }
        $userId = $userGrant->user_id;
        $user = User::find($userId);
        $salt = $user->register_salt;
        if($userGrant->grant_login_credential !== $this->buildPassword($password,$salt)){
            throw new BusinessException("账户和密码错误！",UserErrorCode::LOGIN_PASSWORD_ERROR);
        }
        return $this->setLogin($user,$userGrant,$phone);
    }

    public function get($userId,bool $isRealName = false){
        $userId = intval($userId);
        if($userId <= 0){
            return [];
        }
        $user = User::find($userId);
        if(empty($user)){
            return [];
        }
        $user['has_pay_password'] = !empty($user['pay_password']);
        $user = $user->toArray();
        $userGrantPhone = (new UserGrantLogin())->findByUserPhone($userId);
        $user['phone'] = '';
        if($userGrantPhone){
            $user['phone'] = $userGrantPhone->grant_login_identify;
        }

        $realName = [];
        if($isRealName) {
            $realName = (new UserRealName)->where('user_id', $userId)->select('user_id', 'name', 'sex', 'birth', 'address', 'idcard', 'frontPhoto', 'backPhoto')->first();
        }
        $user['real_name'] = $realName['name'] ?? '';
        $user['sex'] = $realName['sex'] ?? '未知';
        $user['home_address'] = $realName['address'] ?? '';
        return $user;
    }

    /**
     * @param $phone
     * @param $code
     * @param $password
     * @param $rePassword
     * @param string $token
     * @return bool
     * @throws BusinessException
     */
    public function resetPassword($phone,$code,$password,$rePassword,$token='')
    {
        $code = trim($code);
        $phone = trim($phone);
        $data = [
            'phone'=>$phone,
            'code'=>$code,
            'password'=>$password,
            'password_confirmation'=>$rePassword
        ];
        $validate = Validator::make($data,[
            'phone'=>'required',
            'code'=>'required',
            'password'=>'required|min:6|max:15',
            'password_confirmation'=>['required',"same:password"]
        ],[
            'phone.required' => '手机号不能为空',
            'code.required' => '验证码不能为空',
            'password.required'=>"密码不能为空",
            'password_confirmation.required'=>"确认密码不能为空",
            'password_confirmation.same'=>'密码与确认密码不匹配',
            'password.min' => "密码长度最小是6位"
        ]);
        if($validate->fails()){
            throw new BusinessException($validate->errors()->first());
        }
        $grantModel = new UserGrantLogin();
        $registerUser = $grantModel->findByGrantType(UserConst::GRANT_LOGIN_TYPE_PHONE,$phone);
        if(!$registerUser){
            throw new BusinessException("该手机号还没有注册哦！",UserErrorCode::INFO_PHONE_NOT_EXIST);
        }
        $user = User::find($registerUser->user_id);
        $registerCode = $this->checkPhoneCode($phone,$code,SmsConst::BUSINESS_TYPE_PASSWORD);
        $registerUser->grant_login_credential = $this->buildPassword($password,$user->register_salt);
        $flag = $registerUser->save();
        $registerCode->is_use = 1;
        $registerCode->save();
        if($flag && $token){
            $this->loginOut($token);
        }
        return $flag;
    }



    public function loginOut($token){
        if(!trim($token)){
            throw new BusinessException("登录信息不存在",UserErrorCode::INFO_NOT_EXIST);
        }
        $loginData = Cache::get($token);
        if(empty($loginData)){
            return true;
        }
        $userId = $loginData['user_id'];
        $salt = $loginData['salt'];
        $fixedKey = $this->buildFixedKey($userId,$salt);
        Cache::delete($fixedKey);
        Cache::delete($token);
        return true;
    }

    /**
     * @param $phone
     * @param $code
     * @return array
     * @throws BusinessException
     */
    public function loginPhoneWithCode($phone,$code){
        $code = trim($code);
        $phone = trim($phone);
        $grantModel = new UserGrantLogin();
        /** @var UserGrantLogin $userGrant */
        $userGrant = $grantModel->findByGrantType(UserConst::GRANT_LOGIN_TYPE_PHONE,$phone);
        if(empty($userGrant)){
            throw new BusinessException("您还没有注册，请先注册",UserErrorCode::INFO_PHONE_NOT_EXIST);
        }
        $userId = $userGrant->user_id;
        $user = User::find($userId);
        $registerCode = $this->checkPhoneCode($phone,$code,SmsConst::BUSINESS_TYPE_LOGIN);
        $registerCode->is_use = 1;
        $registerCode->save();
        return $this->setLogin($user,$userGrant,$phone);
    }

    /**
     * @param $phone
     * @param $code
     * @param int $thirdPartyId
     * @return array
     * @throws BusinessException
     */
    public function quickLoginPhoneWithCode($phone,$code,$thirdPartyId = 0){
        $code = trim($code);
        $phone = trim($phone);
        $grantModel = new UserGrantLogin();

        /** @var UserGrantLogin $userGrant */
        $userGrant = $grantModel->findByGrantType(UserConst::GRANT_LOGIN_TYPE_PHONE,$phone);
        if(empty($userGrant)){
            $password = quick_random(10);
            return $this->registerByPhone($phone,$code,$password,$password,$thirdPartyId);
        }
        $userId = $userGrant->user_id;
        $user = User::find($userId);
        $registerCode = $this->checkPhoneCode($phone,$code,SmsConst::BUSINESS_TYPE_REGISTER);
        $registerCode->is_use = 1;
        $registerCode->save();
        if($thirdPartyId > 0 && $thirdPartyId != $userGrant->user_id){
            $thirdPartUser = $grantModel->where(['user_id'=>$thirdPartyId])->orderByDesc('grant_login_id')->first();
            $grantModel->where(['user_id'=>$thirdPartyId])->update(['user_id'=>$userGrant->user_id]);
            User::where(['user_id'=>$thirdPartyId])->delete();
            if($user && empty($user->user_avatar) && $thirdPartUser){
                $user->user_avatar = $thirdPartUser->grant_user_avatar;
                $user->user_name = $thirdPartUser->grant_user_nickname;
                $user->save();
            }

        }
        return $this->setLogin($user,$userGrant,$phone);
    }

    /**
     * @param array $thirdUser
     * @return array
     * @throws BusinessException
     */
    public function thirdPartyAppLogin(array $thirdUser){
        $validate = Validator::make($thirdUser,[
            'grant_login_type'=>['required',Rule::in(array_reverse(UserConst::thirdPartyLoginTypes()))],
            'grant_login_identify'=>'required',
            'grant_login_credential'=>'required',
            'grant_user_nickname'=>'required'
        ],[
            'grant_login_type.required' => '登录类型不能为空',
            'grant_login_identify.required' => '用户标识不能为空',
            'grant_login_credential.required' => '用户AccessToken不能为空',
            'grant_user_nickname.required' => '用户昵称不能为空'
        ]);
        if($validate->fails()){
            throw new BusinessException($validate->errors()->first(),UserErrorCode::REGISTER_VALIDATION_ERROR);
        }
        $grantType = UserConst::getThirdGrantLoginType($thirdUser['grant_login_type']);
        $grantModel = new UserGrantLogin();
        $userId = $thirdUser['user_id'] ?? 0;
        $registerUser = $grantModel->findByGrantType($grantType,$thirdUser['grant_login_identify']);
        if($registerUser) {
            $user = User::find($registerUser->user_id);
            $userId = $registerUser->user_id;
        }else{
            $user  = User::find($userId);
        }
        $phoneUser = $grantModel->findByUserPhone($userId) ?? null;
        try {

            DB::beginTransaction();
            $userModel = null;
            if( empty($registerUser) && empty($user)){
                $user = new User();
                $user->user_name = $thirdUser['grant_user_nickname'];
                $user->user_status = 1;
                $user->register_salt = quick_random(6);
                $user->register_type = $grantType;
                $user->register_ip = ip2long(request()->ip());
                $user->user_avatar =  $thirdUser['grant_user_avatar'];
                $user->save();
                $userId = $user->user_id;
                $thirdUser['user_id'] = $userId;
                if($userId <= 0){
                    throw new BusinessException("注册失败",UserErrorCode::REGISTER_FAILED);
                }
            }
            if(empty($registerUser)){
                $grantModel->user_id = $userId;
                $grantModel->grant_login_type = $grantType;
                $grantModel->grant_login_identify = $thirdUser['grant_login_identify'];
                $grantModel->grant_login_credential = $thirdUser['grant_login_credential'];
                $grantModel->grant_user_nickname = $thirdUser['grant_user_nickname'];
                $grantModel->grant_user_avatar = $thirdUser['grant_user_avatar'];
                $grantModel->grant_login_ip = ip2long(request()->ip());
                $grantModel->grant_status = 1;
                $grantModel->save();
            }else{
                $registerUser->grant_user_nickname = $thirdUser['grant_user_nickname'];
                $registerUser->grant_login_credential = $thirdUser['grant_login_credential'];
                $registerUser->grant_user_avatar = $thirdUser['grant_user_avatar'];
                $registerUser->update();
            }
            if($user && $userId == $thirdUser['user_id']){
                if(!$user->user_name) {
                    $user->user_name = $thirdUser['grant_user_nickname'] ?? $phoneUser->user_name;
                }
                if(!$user->user_avatar) {
                    $user->user_avatar = $thirdUser['grant_user_avatar'] ?? $phoneUser->user_avatar;
                }
                $user->update();
            }
            DB::commit();
            return $this->setLogin($user,$registerUser ?: $grantModel,$phoneUser->grant_login_identify ?? '');
        }catch (\Exception $e){
            \Log::error("user register failed third message:{$e->getMessage()} data: ".json_encode($thirdUser));
            DB::rollBack();
            throw new BusinessException($e->getMessage(),$e->getCode());
        }
    }

    public function buildPassword($password,$salt){
        return  sha1(md5($password).$salt);
    }

    protected function buildFixedKey($userId,$salt){
        return 'login'.$userId.$salt;
    }

    /**
     *
     * @param string $phone 手机号
     * @param int $code 验证码
     * @param int $businessType 业务类型
     * @throws BusinessException
     * @return UserCodeVerify
     */
    protected function checkPhoneCode($phone,$code,$businessType){
        $codeService = new CodeService();
        return $codeService->checkPhoneCode($phone,$code,$businessType);
    }

    /**
     * @param $phone
     * @param $code
     * @param $password
     * @param $rePassword
     * @return bool
     * @throws BusinessException
     */
    public function resetPayPassword($userId,$phone,$code,$password,$rePassword)
    {
        $code = trim($code);
        $phone = trim($phone);
        $data = [
            'user_id' => $userId,
            'phone'=>$phone,
            'code'=>$code,
            'pay_password'=>$password,
            'pay_password_confirmation'=>$rePassword
        ];
        $validate = Validator::make($data,[
            'phone'=>'required',
            'code'=>'required',
            'user_id' => 'required',
            'pay_password'=>'required|min:6|max:15',
            'pay_password_confirmation'=>['required',"same:pay_password"]
        ],[
            'phone.required' => '手机号不能为空',
            'code.required' => '验证码不能为空',
            'user_id.required' => '您还没有登录，请先登录',
            'pay_password.required'=>"支付密码不能为空",
            'pay_password_confirmation.required'=>"确认支付密码不能为空",
            'pay_password_confirmation.same'=>'密码与确支付认密码不匹配',
            'pay_password.min' => "密码长度最小是6位"
        ]);
        if($validate->fails()){
            throw new BusinessException($validate->errors()->first(),UserErrorCode::MODIFY_PASSWORD_ERROR);
        }
        $grantLoginModel = new UserGrantLogin();
        $registerUser = $grantLoginModel->findByGrantType(UserConst::GRANT_LOGIN_TYPE_PHONE,$phone);
        if(!$registerUser){
            throw new BusinessException("该手机号还没有注册哦！",UserErrorCode::INFO_PHONE_NOT_EXIST);
        }
        if($registerUser['user_id'] != $userId){
            throw new BusinessException("手机号码不是当前用户登录手机号",UserErrorCode::MODIFY_PHONE_ERROR);
        }
        $user = User::find($registerUser->user_id);
        if(empty($user)){
            throw new BusinessException("当前用户信息不存在",UserErrorCode::INFO_NOT_EXIST);
        }
        $encryptPassword = $this->buildPassword($password,$user->register_salt);
        $registerCode = $this->checkPhoneCode($phone,$code,SmsConst::BUSINESS_TYPE_PAY_PASSWORD);
        $user->pay_password = $encryptPassword;
        $flag = $user->save();
        $registerCode->is_use = 1;
        $registerCode->save();
        $this->addUserChangeLog($userId,UserConst::CHANGE_LOG_TYPE_PASSWORD,$registerUser['grant_login_credential'],$encryptPassword,$userId);
        return $flag;
    }

    /**
     * @param $userId
     * @param $newPhone
     * @param $code
     * @return bool
     * @throws BusinessException
     */
    public function modifyUserPhone($userId,$newPhone,$code){
        $data = [
            'user_id' => $userId,
            'phone'=>$newPhone,
            'code'=>$code
        ];
        $validate = Validator::make($data,[
            'phone'=>'required',
            'code'=>'required',
            'user_id' => 'required'

        ],[
            'phone.required' => '需要更换的手机号不能为空',
            'code.required' => '验证码不能为空',
            'user_id.required' => '您还没有登录，请先登录'
        ]);
        if($validate->fails()){
            throw new BusinessException($validate->errors()->first());
        }
        $user = User::find($userId);
        if(empty($user)){
            throw new BusinessException("登录用户不存在",UserErrorCode::INFO_NOT_EXIST);
        }
        $grantLoginModel = new UserGrantLogin();
        $newRegisterUser = $grantLoginModel->findByGrantType(UserConst::GRANT_LOGIN_TYPE_PHONE,$newPhone);
        if($newRegisterUser){
            throw new BusinessException("您要更换的手机号已在平台注册过，请联系客服！",UserErrorCode::REGISTER_PHONE_EXIST);
        }
        $currentRegister = $grantLoginModel->findByUserPhone($userId);
        $oldPhone = $currentRegister['grant_login_identify'];
        if($currentRegister['grant_login_identify'] == $newPhone){
            throw new BusinessException("您要更换的手机号是您当前登录手机号",UserErrorCode::MODIFY_PHONE_ERROR);
        }
        $registerCode = $this->checkPhoneCode($newPhone,$code,SmsConst::BUSINESS_TYPE_UPDATE_PHONE);
        $currentRegister->grant_login_identify = $newPhone;
        $currentRegister->save();
        $registerCode->is_use = 1;
        $registerCode->save();
        $this->addUserChangeLog($userId,UserConst::CHANGE_LOG_TYPE_PHONE,$oldPhone,$newPhone,$userId);

        // 更新推送绑定手机号
        $this->getPushBridge()->bindPhone($userId, $newPhone);
        return true;
    }

    protected function addUserChangeLog($userId,$changeType,$originValue,$modifyValue,$modifyUserId){
        $changeLog = new ChangeLog();
        $changeLog->user_id = $userId;
        $changeLog->change_type = $changeType;
        $changeLog->origin_value = $originValue;
        $changeLog->modify_value = $modifyValue;
        $changeLog->modify_user_id = $modifyUserId;
        $changeLog->modify_ip = ip2long(request()->ip());
        $changeLog->save();
    }

    protected function setLogin(User $user,UserGrantLogin $grantLogin,$phone = ''){
        $loginRecord = new UserLoginRecord();
        $loginRecord->user_id = $grantLogin->user_id;
        $loginRecord->login_correct = 1;
        $loginRecord->grant_login_id = $grantLogin->grant_login_id;
        $loginRecord->grant_login_type =$grantLogin->grant_login_type;
        $loginRecord->login_ip = ip2long(request()->ip());
        $loginRecord->save();

        $cacheData = [
            'user_id'=>$grantLogin->user_id,
            'phone'=>$phone,
            'salt'=>$user->register_salt,
            'grant_type'=>$grantLogin->grant_login_type,
            'grant_identify'=>$grantLogin->grant_login_identify,
            'grant_login_id'=> $grantLogin->grant_login_id,
            'is_certification' => $user['is_certification'] ?? 0
        ];
        $expireHours = env('LOGIN_EXPIRE_HOUR',24);
        $expiredDate = now()->addHours($expireHours);
        $fixedKey = $this->buildFixedKey($grantLogin->user_id,$user->register_salt);
        $frontToken = md5($fixedKey.quick_random(8));//返回给前端的加蜜登录token
        $preToken = Cache::get($fixedKey);
        if($preToken) {
            Cache::delete($preToken);
        }
        Cache::put($fixedKey,$frontToken,$expiredDate);
        Cache::put($frontToken,$cacheData,$expiredDate);
        $cacheData['token'] = $frontToken;
        return $cacheData;
    }

    public function users(array $userIds,bool $isRealName = false) : array
    {
        if(empty($userIds)){
            return [];
        }
        $users = User::findMany($userIds);
        if(empty($users)){
            return [];
        }
        $realNames = [];
        if($isRealName) {
            $realNames = (new UserRealName)->whereIn('user_id', $userIds)->select('user_id', 'name', 'sex', 'birth', 'address', 'idcard', 'frontPhoto', 'backPhoto')->get()->keyBy('user_id')->toArray();
        }
        $users = $users->keyBy('user_id')->map(function ($user,$userId) use ($realNames){
            $realName = $realNames[$userId] ?? [];
            $user['real_name'] = $realName['name'] ?? '';
            $user['idcard'] = $realName['idcard'] ?? '';
            $user['sex'] = $realName['sex'] ?? '未知';
            $user['home_address'] = $realName['address'] ?? '未知';
            return $user;
        })->toArray();

        return $users;
    }

    public function user(int $userId,bool $isRealName = false) : array
    {
        if($userId <= 0){
            return [];
        }
        $users = $this->users([$userId],$isRealName);
        return $users[$userId] ?? [];
    }

    /**
     * 根据用户名查询用户
     * @param $username
     * @return \Illuminate\Database\Eloquent\Builder|Model|object|null
     */
    public function getUserByUsername($username)
    {
        return User::where('user_name', $username)->first();
    }

    /**
     * 用户列表
     * @param $filter
     * @param $pageSize
     * @param $columns
     * @return array|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getListByPage($filter, $pageSize, $columns = ['*'])
    {
        $list = User::when(!empty($filter['status']), function ($query) use ($filter) {
                $query->where('user_status', $filter['status']);
            })->when(!empty($filter['is_certification']), function ($query) use ($filter) {
                $query->where('is_certification', $filter['is_certification']);
            })->when(!empty($filter['type']), function ($query) use ($filter) {
                $query->where('register_type', $filter['type']);
            })->when(!empty($filter['keyword']), function ($query) use ($filter) {
                $query->where('user_name', 'LIKE', '%' . $filter['keyword'] . '%');
            })
            ->select($columns)
            ->orderByDesc('user_id')->paginate($pageSize);
        if(empty($list)){
            return [];
        }
        return $list;
    }

    /**
     * 获取实名认证信息
     * @param $userId
     * @return array
     * @throws BusinessException
     */
    public function getCertification($userId)
    {
        $realName = UserRealName::where('user_id', $userId)->first();
        if (!$realName) {
            throw new BusinessException('实名信息未找到');
        }
        $face = UserFace::where('user_id', $userId)->where('business_type', UserConst::FACE_AUTH_TYPE_HELPER)->first();
        if (!$face) {
            throw new BusinessException('扫脸信息未找到');
        }

        return array_merge($realName->toArray(), $face->toArray());
    }

    /**
     * 冻结用户
     * @param $userId
     * @return bool
     */
    public function frozen($userId)
    {
        return User::where('user_id', $userId)->update(['user_status' => 0]) !== false;
    }

    /**
     * 解冻用户
     * @param $userId
     * @return bool
     */
    public function unFrozen($userId)
    {
        return User::where('user_id', $userId)->update(['user_status' => 1]) !== false;
    }

    /**
     * @param $grantType
     * @param $grantIdentify
     * @return array
     */
    public function findByGrantType($grantType,$grantIdentify)
    {
        $user = (new UserGrantLogin)->findByGrantType($grantType,$grantIdentify);
        return $user ? $user->toArray() : [];
    }

    public function findByUserGrantId($userId, $id){
        return (new UserGrantLogin)->findByUserGrantId($userId,$id);
    }

    public function findByUserGrantType($userId, $grantType){
        return (new UserGrantLogin)->findByUserGrantType($userId,$grantType);
    }

    public function saveUserAddressPosition(array $address,$userId,$yunTuId = 0){
        $newAddress = [];
        $model = $this->getUserAddressPositionModel();
        $exist = $model->where('user_id',$userId)->first();
        if($exist){
            $model = $exist;
        }
        $newAddress['province'] = $address['province'] ?? '';
        $newAddress['city'] = $address['city'] ?? '';
        $newAddress['region'] = $address['region'] ?? '';
        $newAddress['street'] = $address['street'] ?? '';
        $newAddress['address_detail'] = $address['address_detail'] ?? '';
        $newAddress['lng'] = $address['lng'] ?? '';
        $newAddress['lat'] = $address['lat'] ?? '';
        $newAddress['user_id'] = $userId;
        $newAddress['yuntu_id'] = $yunTuId;
        $fields = $model->getTableColumns();
        foreach ($fields as $field) {
            if ($field == $model->getKeyName()) {
                continue;
            }
            if (isset($newAddress[$field])) {
                $model->$field = $newAddress[$field];
            }
        }
        $model->save();
        if( $newAddress['lng'] &&  $newAddress['lat'] ) {
           //$this->updateConfigPosition($userId, $address['lng'], $address['lat']);
        }
        return $model['id'];
    }

    public function deletePositionByUserIds(array $userIds){
        return $this->getUserAddressPositionModel()->whereIn('user_id',$userIds)->delete();
    }

    public function getPositionByUserIds(array $userIds){
        if(empty($userIds)){
            return [];
        }
        return $this->getUserAddressPositionModel()->select(['user_id','lng','lat'])->whereIn('user_id',$userIds)->get()->keyBy('user_id')->toArray();
    }

    public function getAcceptConfigByUserIds(array $userIds){
        if(empty($userIds)){
            return [];
        }
        return $this->getAcceptConfigModel()->select(['user_id','employer_distance','lng','lat'])->whereIn('user_id',$userIds)->get()->keyBy('user_id')->toArray();
    }

    public function judgeUserBlackList($userId,array $judgeUserIds){
        if($userId <= 0 || empty($judgeUserIds)){
            return [];
        }
        return $this->getUserBlackModel()->select('black_user_id')->whereIn('black_user_id',$judgeUserIds)->where('user_id',$userId)->get()->pluck('black_user_id')->toArray();
    }

    public function saveDefaultConfig($userId){
       $acceptConfigModel = $this->getAcceptConfigModel();
       $acceptConfig = $acceptConfigModel->where('user_id')->first();
       if($acceptConfig){
           return $acceptConfig->toArray();
       }
       $acceptConfigModel->user_id = $userId;
       $acceptConfigModel->employer_level = 0;
       $acceptConfigModel->employer_price = 0;
       $acceptConfigModel->employer_distance = 0;
       $acceptConfigModel->start_at = 0;
       $acceptConfigModel->end_at = 2400;
       $acceptConfigModel->save();
       return $acceptConfigModel->toArray();
    }

    public function updateConfigPosition($userId,$lng,$lat){
        if($userId <= 0){
            return false;
        }
        $acceptConfigModel = $this->getAcceptConfigModel();
        $acceptConfig = $acceptConfigModel->where('user_id',$userId)->first();
        if(empty($acceptConfig)){
            return false;
        }
        $acceptConfig->lng = $lng;
        $acceptConfig->lat = $lat;
        $acceptConfig->save();
        return true;
    }

    public function search(array $filter){
        if(empty($filter)){
            return [];
        }
        $model =  User::getModel()
            ->when(!empty($filter['user_name']), function ($query) use ($filter) {
                $query->where('user_name', 'LIKE',  $filter['user_name'] . '%');
            });

        return $model->get()->keyBy('user_id')->toArray();
    }

    public function bindAlipay($userId,$alipayUserId,$nickName,$avatar,$accessToken){
        if($userId <=0 || empty($alipayUserId)){
            return false;
        }
        $user  = User::find($userId);
        if(empty($user)){
            return false;
        }
        $grantModel = (new UserGrantLogin())->findByGrantType(UserConst::GRANT_LOGIN_TYPE_ALIPAY,$alipayUserId);
        if(empty($grantModel)){
            $grantModel = new UserGrantLogin();
            $grantModel->user_id = $userId;
            $grantModel->grant_login_type = UserConst::GRANT_LOGIN_TYPE_ALIPAY;
            $grantModel->grant_login_identify = $alipayUserId;
            $grantModel->grant_login_credential = $accessToken;
            $grantModel->grant_user_nickname =$nickName;
            $grantModel->grant_user_avatar = $avatar;
            $grantModel->grant_login_ip = ip2long(request()->ip());
            $grantModel->grant_status = 1;
            $grantModel->save();
        }else{
            $grantModel->grant_user_nickname = $nickName;
            $grantModel->grant_login_credential = $accessToken;
            $grantModel->grant_user_avatar = $avatar;
            $grantModel->update();
        }
        if($user && $userId == $grantModel['user_id']){
            if(!$user->user_name && $nickName){
                $user->user_name = $nickName;
            }
            if(!$user->user_avatar && $avatar){
                $user->user_avatar = $avatar;
            }
            $user->update();
        }
        return true;
    }

    public function getDetail($userId)
    {
        $user = User::where('user_id', $userId)->first()->toArray();
        if (!$user) {
            throw new BusinessException('用户未找到');
        }
        $user['register_ip'] = long2ip($user['register_ip']);

        // 授权登录信息
        $user['grant_login'] = UserGrantLogin::where('user_id', $userId)->orderByDesc('updated_at')->get()->toArray();

        // 常用地址
        $user['address_list'] = UserAddress::where('user_id', $userId)->orderByDesc('created_at')->get()->toArray();

        // 帮手技能
        if ($user['is_certification']) {
            $taskCategory = $this->getTaskCategoryService()->getDic();

            $user['skill_certify'] = UserSkillCertify::where('user_id', $userId)->orderByDesc('created_at')->get()->toArray();
            foreach ($user['skill_certify'] as &$item) {
                $item['card_type'] = CertificateConst::licenseList()[$item['card_type']] ?? '';
                $item['order_category'] = $taskCategory[$item['order_category']];
            }

            // 帮手认证时间
            $user['helper_cert_at'] = UserFace::where('user_id', $userId)->where('business_type', UserConst::FACE_AUTH_TYPE_HELPER)->min('created_at');
        } else {
            $user['skill_certify'] = [];
        }

        return $user;
    }

    /**
     * @return TaskCategoryManagerService
     */
    protected function getTaskCategoryService()
    {
        return new TaskCategoryManagerBridge(new TaskCategoryManagerService(new Category()));
    }

    protected function getAcceptConfigModel(){
        return new UserAcceptConfig();
    }

    protected function getUserAddressPositionModel(){
        return new UserAddressPosition();
    }

    protected function getUserBlackModel(){
        return new UserBlack();
    }
    /**
     * @return PushService
     */
    protected function getPushBridge(){
        return (new PushBridge(new PushService()));
    }

}
