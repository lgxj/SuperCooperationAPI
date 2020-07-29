<?php
namespace App\Http\Controllers\Message;

use App\Bridges\User\CustomerBridge;
use App\Http\Controllers\Controller;
use App\Services\Message\IMService;
use App\Services\User\CustomerService;
use Illuminate\Http\Request;

class IMController extends Controller
{
    protected $imService;
    /**
     * @var CustomerService
     */
    protected $customerService;

    public function __construct(IMService $imService, CustomerBridge $customerBridge)
    {
        $this->imService = $imService;
        $this->customerService = $customerBridge;
    }

    /**
     * 获取登录信息
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getLoginParams()
    {
        $result = $this->imService->getLoginParams($this->getUserId());
        return success($result);
    }

    /**
     * 获取用户IM在线状态
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserOnlineState(Request $request)
    {
        $id = $request->input('id');
        $result = $this->imService->getUserOnlineState($id);
        return success($result);
    }

    /**
     * 客服列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomerService()
    {
        $result = $this->customerService->getCustomerService();
        return success($result);
    }

    public function customerService(Request $request){
        $customerUserId = $this->customerService->getCurrentCustomer($this->getUserId());
        $chatUrl = "/pages/message/chat?userId={$customerUserId}";
        return success(['chatUrl'=>$chatUrl]);
    }

}
