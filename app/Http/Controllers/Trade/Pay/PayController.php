<?php


namespace App\Http\Controllers\Trade\Pay;


use App\Http\Controllers\Controller;
use App\Services\Trade\Pay\PayTaskOrderService;
use Illuminate\Http\Request;

class PayController extends Controller
{
    protected $payOrderService;

    public function __construct(PayTaskOrderService $payOrderService)
    {
        $this->payOrderService = $payOrderService;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function pay(Request $request){
        $channel = $request->get('channel');
        $payType = $request->get('pay_type');
        $stepType = $request->get('step_type');
        $orderNo = $request->get('order_no');
        $payPrice = $request->get('pay_price');
        $platformOpenId = $request->get('platformOpenId','');
        $payPassword = $request->get('pay_password','');
        $payResult = $this->payOrderService->pay($this->getUserId(),$channel,$payType,$payPrice,$stepType,$orderNo,$platformOpenId,$payPassword);
        return success($payResult);

    }
}
