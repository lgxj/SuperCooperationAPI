<?php


namespace App\Http\Controllers\User;


use App\Bridges\Trade\AccountBridge;
use App\Bridges\Trade\EmployerBridge;
use App\Bridges\Trade\HelperBridge;
use App\Consts\SmsConst;
use App\Consts\Trade\OrderConst;
use App\Consts\UserConst;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Services\Trade\Fund\AccountService;
use App\Services\Trade\Order\Employer\EmployerService;
use App\Services\Trade\Order\Helper\HelperService;
use App\Services\User\CodeService;
use App\Services\User\LabelService;
use App\Services\User\SkillCertifyService;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{

    protected $userService;

    protected $codeService;

    public function __construct(UserService $userService,CodeService $codeService)
    {
        $this->userService = $userService;
        $this->codeService = $codeService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function sendCode(Request $request){

        $codeType = (int)$request->input('code_type',SmsConst::CODE_TYPE_PHONE);
        $businessType = (int)$request->input('business_type',SmsConst::BUSINESS_TYPE_REGISTER);
        $account = $request->input('account','');
        $userId = 0;
        $verifyId = $this->codeService->sendCode($userId,$account,$businessType,$codeType,SmsConst::CHANNEL_ALIYUN);
        return success(['verify_id'=>$verifyId]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function register(Request $request){
        $phone = $request->get('phone');
        $code = $request->get('code');
        $password = $request->get('password');
        $user = $this->userService->registerByPhone($phone,$code,$password,$password);
        return success(['SC_ACCESS_TOKEN'=>$user['token']]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function loginWithPassword(Request $request){
        $phone = $request->get('phone');
        $password = $request->get('password');
        $user = $this->userService->loginWithPhonePass($phone,$password);
        return success(['SC_ACCESS_TOKEN'=>$user['token']]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function loginOut(Request $request){
        $accessToken = $request->header('SC-ACCESS-TOKEN','');
        $this->userService->loginOut($accessToken);
        return success();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function get(Request $request){
        $userId = $request->get('user_id',$this->getUserId());
        $user = $this->userService->get($userId ?? 0,true);
        $userType =  $request->get('user_type',null);
        $isTaskTotal =  $request->get('is_task_total',false);
        $isAccount =  $request->get('is_account',false);
        $isSkill =  $request->get('is_skill',false);
        $userId = intval($userId);
        $user['hot_labels'] = [];
        $user['skill'] = [];
        $user['task_total'] = 0;
        $user['available_balance'] = 0;
        $user['freeze'] = 0;
        if(!is_null($userType) && $user){
            $user['hot_labels'] = $this->getLabelService()->getUserHotLabels($userId,$userType);
        }
        if($isTaskTotal && !is_null($userType) && $user){
            if($userType == UserConst::LABEL_TYPE_EMPLOYER){
                $user['task_total'] = $this->getEmployerBridge()->countTask($userId,[]);
            }else{
                $user['task_total'] = $this->getHelperBridge()->countTask($userId,[OrderConst::HELPER_STATE_COMPLETE]);
            }
        }
        if($isAccount){
           $account = $this->getAccountBridge()->getAccountByUserId($userId);
           $user['available_balance'] = display_price($account['available_balance']);
           $user['freeze'] = display_price($account['freeze']);
        }
        if($isSkill){
           $skillList =  $this->getSkillService()->findAllByUid($userId);
           $user['skills'] = collect($skillList)->pluck('type_desc');
        }
        return success($user);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function resetPassword(Request $request){
        $code = $request->get('code');
        $phone = $request->get('phone');
        $password = $request->get('password');
        $accessToken = $request->header('SC-ACCESS-TOKEN','');
        $flag = $this->userService->resetPassword($phone,$code,$password,$password,$accessToken);
        return success(['flag'=>$flag]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function resetPayPassword(Request $request){
        $code = $request->get('code');
        $phone = $request->get('phone');
        $password = $request->get('password');
        $confirmPassword = $request->get('confirm_password');
        $flag = $this->userService->resetPayPassword($this->getUserId(),$phone,$code,$password,$confirmPassword);
        return success(['flag'=>$flag]);
    }
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function loginWithPhoneCode(Request $request){
        $phone = $request->get('phone');
        $code = $request->get('code');
        $user = $this->userService->loginPhoneWithCode($phone,$code);
        return success(['SC_ACCESS_TOKEN'=>$user['token']]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function quickRegisterAndLogin(Request $request){
        $phone = $request->get('phone');
        $code = $request->get('code');
        $user_id = $request->get('user_id',0);
        $user = $this->userService->quickLoginPhoneWithCode($phone,$code,$user_id);
        return success(['SC_ACCESS_TOKEN'=>$user['token']]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function thirdPartyAppLogin(Request $request){
        $login =  $request->get('userLogin');
        $thirdUser['grant_login_type'] = $request->get('grant_login_type');
        $thirdUser['grant_login_identify'] = $request->get('grant_login_identify');
        $thirdUser['grant_login_credential'] = $request->get('grant_login_credential');
        $thirdUser['grant_user_nickname'] = $request->get('grant_user_nickname');
        $thirdUser['grant_user_avatar'] = $request->get('grant_user_avatar');
        $thirdUser['user_id'] = $login['user_id'] ?? 0;
        $user = $this->userService->thirdPartyAppLogin($thirdUser);
        return success(['SC_ACCESS_TOKEN'=>$user['token'],'user_id'=>$user['user_id'],'has_phone'=> !empty($user['phone'])]);
    }

    /**
 * 更新用户信息
 *
 * @param Request $request
 * @return bool
 * @throws BusinessException
 */
    public function updateUserBaseInfo(Request $request){
        $userName = $request->get('user_name','');
        $userAvatar = $request->get('user_avatar','');
        $list = $this->userService->updateUserBaseInfo($this->getUserId(),$userName,$userAvatar);
        return success($list);
    }

    /**
     * 更新用户信息
     *
     * @param Request $request
     * @return bool
     * @throws BusinessException
     */
    public function updateUserPhone(Request $request){
        $newPhone = $request->get('phone','');
        $code = $request->get('code','');
        $this->userService->modifyUserPhone($this->getUserId(),$newPhone,$code);
        return success([]);
    }

    /**
     * 更新用户信息
     *
     * @param Request $request
     * @return bool
     * @throws BusinessException
     */
    public function updateUserAccept(Request $request){
        $accept = $request->get('is_accept_order',1);
        $data = $this->userService->updateUserAccept($this->getUserId(),$accept);
        return success($data);
    }

    protected function getLabelService(){
        return new LabelService();
    }

    /**
     * @return EmployerService
     */
    protected function getEmployerBridge(){
        return new EmployerBridge(new EmployerService());
    }

    /**
     * @return HelperService
     */
    protected function getHelperBridge(){
        return new HelperBridge(new HelperService());
    }
    /**
     * @return AccountService
     */
    protected function getAccountBridge(){
        return new AccountBridge(new AccountService());
    }

    protected function getSkillService(){
        return new SkillCertifyService();
    }

}
