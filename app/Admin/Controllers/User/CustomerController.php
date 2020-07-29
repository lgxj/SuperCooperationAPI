<?php
/**
 * 客服管理
 */
namespace App\Admin\Controllers\User;

use App\Admin\Controllers\ScController;
use App\Bridges\User\CustomerBridge;
use App\Services\User\CustomerService;
use App\Bridges\Message\MessageBridge;
use App\Services\Message\MessageService;
use App\Bridges\Permission\AdminLogBridge;
use App\Services\Permission\AdminLogService;
use Illuminate\Http\Request;

class CustomerController extends ScController
{

    /**
     * @var CustomerService
     */
    protected $service;

    /**
     * @var AdminLogService
     */
    protected $adminLogService;

    /**
     * @var MessageService
     */
    protected $messageService;

    public function __construct(CustomerBridge $service, AdminLogBridge $adminLogBridge, MessageBridge $messageService)
    {
        $this->service = $service;
        $this->adminLogService = $adminLogBridge;
        $this->messageService = $messageService;
    }

    /**
     * 列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getList(Request $request)
    {
        $result = $this->service->getCustomerService();
        return success($result);
    }

    /**
     * 搜索可添加为客服的用户
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function search(Request $request)
    {
        $username = $request->input('user_name');
        $result = $this->service->searchAbleAddCustomer($username);
        return success($result);
    }

    /**
     * 添加客服
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function add(Request $request)
    {
        $userId = $request->input('user_id');
        $result = $this->service->addCustomer($userId);
        if ($result) {
            // 记录日志
            $this->adminLogService->create('customer-add', '添加客服账号', 'user_id: ' . $userId);

            return success();
        } else {
            return out(1, '添加失败');
        }
    }

    /**
     * 取消客服
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function cancel(Request $request)
    {
        $userId = $request->input('user_id');
        $result = $this->service->cancelCustomer($userId);
        if ($result) {
            // 记录日志
            $this->adminLogService->create('customer-cancel', '取消客服账号', 'user_id: ' . $userId);

            return success();
        } else {
            return out(1, '取消失败');
        }
    }

    /**
     * 指定客服接待用户列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getServiceUserList(Request $request)
    {
        $customerId = $request->input('customer_id', '');
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $result = $this->messageService->getServiceUserList($customerId, $page, $limit);
        return success($result);
    }

    /**
     * 指定聊天记录明细
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getServiceMsgList(Request $request)
    {
        $messageId = $request->input('message_id', '');
        $order = $request->input('order', 'desc');
        $limit = $request->input('limit', 10);
        $result = $this->messageService->getServiceMsgList($messageId, $order, $limit);
        return success(formatPaginate($result));
    }

}
