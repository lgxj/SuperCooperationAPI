<?php


namespace App\Web\Controllers\Callback;


use App\Bridges\Trade\PayTaskOrderBridge;
use App\Bridges\User\UserBridge;
use App\Consts\ErrorCode\PayErrorCode;
use App\Consts\Trade\PayConst;
use App\Exceptions\BusinessException;
use App\Services\Trade\Pay\PayTaskOrderService;
use App\Services\User\UserService;
use App\Utils\Alipay;
use App\Utils\Dingding;
use App\Utils\UniqueNo;
use App\Web\Controllers\ScController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Yansongda\LaravelPay\Facades\Pay;

class AlipayController extends ScController
{

    /**
     * @var PayTaskOrderService
     */
    protected $payTaskOrderBridge;

    public function __construct(PayTaskOrderBridge $payTaskOrderServiceBridge)
    {
        $this->payTaskOrderBridge = $payTaskOrderServiceBridge;
    }

    /**
     * @param Request $request
     * @return string|Response
     * @throws BusinessException
     */
    public function notify(Request $request){

        /** @var  Alipay $pay */
        $pay = Pay::alipay();

        try{
            $response = $pay->verify();
            if(in_array($response['trade_status'],['TRADE_SUCCESS ','TRADE_FINISHED '])){
                $jsonResponse = json_encode($response->toArray());
                \Log::error("支付宝异步回调失败  data:{$jsonResponse}");
                Dingding::robot(new BusinessException('微信支付异步回调失败'.$jsonResponse,PayErrorCode::NOTIFY_ALIPAY_FAILED));
                return '';
            }
            $payNo = $response['out_trade_no'];
            $outTradeNo = $response['trade_no'];
            $totalFee = db_price($response['total_amount']);
            $this->payTaskOrderBridge->addPayMessage($payNo,PayConst::PAY_MESSAGE_ACTION_PAY,PayConst::PAY_MESSAGE_TYPE_RESPONSE,$response->toArray());
            $payLog = $this->payTaskOrderBridge->getPayLog($payNo);
            $subNoRule = UniqueNo::getInfoByNo($payLog['biz_sub_no']);
            if($subNoRule['business_type'] == UniqueNo::BUSINESS_TYPE_PRICE_CHANGE){
                $this->payTaskOrderBridge->notifyPay($payNo, PayConst::CHANNEL_ALIPAY, $totalFee, $outTradeNo);
            }
        } catch (\Exception $e) {
            \Log::error("支付宝支付异步回调异常  message:{$e->getMessage()}");
            throw new BusinessException($e->getMessage(),PayErrorCode::NOTIFY_ALIPAY_FAILED);
        }
        return $pay->success();

    }


    public function return(Request $request){
        /** @var  Alipay $pay */
        $pay = Pay::alipay();

        try{
            $response = $pay->verify();
            $payNo = $response['out_trade_no'];
            $outTradeNo = $response['trade_no'];
            $totalFee = db_price($response['total_amount']);

        } catch (\Exception $e) {
            \Log::error("支付宝支付返回异常  message:{$e->getMessage()}");
            throw new BusinessException($e->getMessage());
        }
    }

    public function refundNotify(Request $request){

    }


    /**
     * @param Request $request
     * @throws \Exception
     */
    public function login(Request $request){
        $state = $request->get('state');
        $authCode = $request->get('auth_code');
        $return['status'] = 0;
        $return['info'] = '授权失败';
        $loginToken = decrypt($state);
        if($state && $authCode &&  ($login = \Cache::get($loginToken)) ){
            $userId = $login['user_id'];
            $alipay = new Alipay();
            /** @var  UserService $userService */
            $userService = new UserBridge(new UserService());
            $response = $alipay->systemOauthToken($authCode);
            if ($response) {
                $data['access_token'] = $response->access_token;
                $data['refresh_token'] = $response->refresh_token;
                $data['expires_in'] = $response->expires_in;
                $data['re_expires_in'] = $response->re_expires_in;
                $data['user_id'] = $response->user_id;
                $data['avatar'] = '';
                $data['nick_name'] = '';
                $user = $alipay->userInfoShare($data['access_token']);
                if ($user) {
                    $data['user_id'] = $user->user_id;
                    $data['province'] = $user->province ?? '';
                    $data['city'] = $user->city ?? '';
                    $data['is_student_certified'] = $user->is_student_certified ?? '';
                    $data['user_type'] = $user->user_type ?? 0;
                    $data['user_status'] = $user->user_status ?? '';
                    $data['is_certified'] = $user->is_certified ?? '';
                    $data['gender'] = $user->gender ?? '';
                    $data['nick_name'] = $user->nick_name ?? '';
                    $data['avatar'] = $user->avatar ?? '';
                    $data['uid'] = $userId;
                }
                $userService->bindAlipay($userId,$data['user_id'], $data['nick_name'], $data['avatar'],$data['access_token']);
                $return['status'] = 1;
                $return['info'] = '授权成功';
            }
        }
        return view('web/callback/alipay/login', $return);
    }



}
