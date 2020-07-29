<?php


namespace App\Web\Controllers\Callback;


use App\Bridges\Trade\PayTaskOrderBridge;
use App\Consts\ErrorCode\PayErrorCode;
use App\Consts\Trade\PayConst;
use App\Exceptions\BusinessException;
use App\Services\Trade\Pay\PayTaskOrderService;
use App\Utils\Dingding;
use App\Utils\UniqueNo;
use App\Web\Controllers\ScController;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yansongda\LaravelPay\Facades\Pay;
use Yansongda\Pay\Exceptions\InvalidArgumentException;
use Yansongda\Pay\Gateways\Wechat;

class WeixinController extends  ScController
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
     * @throws InvalidArgumentException
     */
    public function notify(Request $request){

        /** @var  Wechat $pay */
        $pay = Pay::wechat();

        try{
            $response = $pay->verify();
            if($response['return_code']  != 'SUCCESS'){
                $jsonResponse = json_encode($response->toArray());
                \Log::error("微信支付异步回调失败  data:{$jsonResponse}");
                Dingding::robot(new BusinessException('微信支付异步回调失败'.$jsonResponse,PayErrorCode::NOTIFY_WEIXIN_FAILED));
                return '';
            }
            $payNo = $response['out_trade_no'];
            $outTradeNo = $response['transaction_id'];
            $totalFee = $response['total_fee'];
            $this->payTaskOrderBridge->addPayMessage($payNo,PayConst::PAY_MESSAGE_ACTION_PAY,PayConst::PAY_MESSAGE_TYPE_RESPONSE,$response->toArray());
            $payLog = $this->payTaskOrderBridge->getPayLog($payNo);
            $subNoRule = UniqueNo::getInfoByNo($payLog['biz_sub_no']);

            if($subNoRule['business_type'] == UniqueNo::BUSINESS_TYPE_PRICE_CHANGE){
                $this->payTaskOrderBridge->notifyPay($payNo, PayConst::CHANNEL_WECHAT, $totalFee, $outTradeNo);
            }
        } catch (\Exception $e) {
            \Log::error("微信支付异步回调异常  message:{$e->getMessage()}");
             throw new BusinessException($e->getMessage(),PayErrorCode::NOTIFY_WEIXIN_FAILED);
        }
        return $pay->success();

    }

    /**
     * @param Request $request
     * @return string|Response
     * @throws BusinessException
     * @throws InvalidArgumentException
     */
    public function refundNotify(Request $request){
        /** @var  Wechat $pay */
        $pay = Pay::wechat();

        try{
            $response = $pay->verify(null,true);
            if($response['return_code']  != 'SUCCESS'){
                $jsonResponse = json_encode($response->toArray());
                \Log::error("微信退款异步回调失败  data:{$jsonResponse}");
                Dingding::robot(new BusinessException('微信退款异步回调失败'.$jsonResponse,PayErrorCode::NOTIFY_WEIXIN_REFUND_FAILED));
                return '';
            }
            $payNo = $response['out_trade_no'];
            $refundNo = $response['out_refund_no'];
            $outTradeNo = $response['transaction_id'];
            $outTradeRefundNo = $response['refund_id'];
            $totalFee = $response['total_fee'];
            $refundFee = $response['refund_fee'];
            $refundStatus = $response['refund_status'];
            $successTime = $response['success_time'];
            $this->payTaskOrderBridge->addPayMessage($payNo,PayConst::PAY_MESSAGE_ACTION_REFUND,PayConst::PAY_MESSAGE_TYPE_RESPONSE,$response->toArray());
            $payLog = $this->payTaskOrderBridge->getPayLog($payNo);
            $subNoRule = UniqueNo::getInfoByNo($payLog['biz_sub_no']);

            if($subNoRule['business_type'] == UniqueNo::BUSINESS_TYPE_PRICE_CHANGE){

            }
        } catch (\Exception $e) {
            \Log::error("微信退款异步回调失败  message:{$e->getMessage()}");
            throw new BusinessException($e->getMessage(),PayErrorCode::NOTIFY_WEIXIN_REFUND_FAILED);
        }
        return $pay->success();

    }
}
