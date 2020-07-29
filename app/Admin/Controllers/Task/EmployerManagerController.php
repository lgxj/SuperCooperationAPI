<?php


namespace App\Admin\Controllers\Task;


use App\Admin\Controllers\ScController;
use App\Bridges\Trade\Admin\EmployerManagerBridge;
use App\Bridges\Trade\CommentBridge;
use App\Bridges\Trade\CompensateBridge;
use App\Bridges\Trade\DetailTaskOrderBridge;
use App\Bridges\Trade\PayTaskOrderBridge;
use App\Consts\MessageConst;
use App\Consts\Trade\OrderConst;
use App\Services\Trade\Fund\CompensateService;
use App\Services\Trade\Order\Admin\EmployerManagerService;
use App\Services\Trade\Order\CommentService;
use App\Services\Trade\Order\Employer\DetailTaskOrderService;
use App\Services\Trade\Pay\PayTaskOrderService;
use Illuminate\Http\Request;

class EmployerManagerController extends ScController
{


    /**
     * @var EmployerManagerService
     */
    protected $managerService;

    public function __construct(EmployerManagerBridge $service)
    {
        $this->managerService = $service;
    }

    public function search(Request $request){
        $filter = $request->input('filter');
        $filter = json_decode($filter, true);
        $pageSize = $request->input('limit');
        $result = $this->managerService->search($filter,$pageSize);
        return success(formatPaginate($result));
    }

    public function detail(Request $request)
    {
        $orderNo = $request->get('order_no','');
        if(empty($orderNo)){
            return fail([],'任务不能为空');
        }
        $withText = $request->get('with_text',true);
        $withService = $request->get('with_service',true);
        $withOrderAddress = $request->get('with_order_address',true);
        $priceChangeType = $request->get('price_change_type','');
        $withUser = $request->get('with_user',true);
        $detailTaskOrderService = $this->getDetailTaskOrderService();
        $taskOrder = $detailTaskOrderService->getOrder($orderNo,$priceChangeType,$withText,$withService,$withOrderAddress,$withUser);

        // 帮手
        $taskOrder['helper'] = $detailTaskOrderService->getReceiver($orderNo, true) ?: null;


        $taskOrder['helper_comments'] = null;
        $taskOrder['employer_comments'] = null;
        $taskOrder['defer'] = [];
        $taskOrder['quoted_list'] = [];
        // 帮手评价雇主
        if(isset($taskOrder['order_state']) && $taskOrder['order_state'] == OrderConst::EMPLOYER_STATE_COMPLETE){
            $list = $this->getCommentService()->getOrderComment($orderNo,MessageConst::TYPE_COMMENT_TASK_EMPLOYER);
            $taskOrder['helper_comments'] = $list[0] ?? null;
        }
        // 雇主评价帮手
        if(isset($taskOrder['order_state']) && $taskOrder['order_state'] == OrderConst::EMPLOYER_STATE_COMPLETE){
            $list = $this->getCommentService()->getOrderComment($orderNo,MessageConst::TYPE_COMMENT_TASK_HELPER);
            $taskOrder['employer_comments'] = $list[0] ?? null;
        }
        // 申请延期交付信息
        if(isset($taskOrder['order_state']) && $taskOrder['order_state'] == OrderConst::EMPLOYER_STATE_RECEIVE){
            $defer = $detailTaskOrderService->getOrderDefersByOrderNo($orderNo,$taskOrder['helper_user_id']);
            $defer = $defer ? $defer->toArray() : [];
            if($defer){
                $taskOrder['defer']['defer_minutes'] = $defer['defer_minutes'];
                $taskOrder['defer']['status'] = $defer['status'];
                $taskOrder['defer']['created_at'] = $defer['created_at'];
                $taskOrder['defer']['status_str'] = OrderConst::getHelperReferStatus($defer['status']);
            }
        }
        // 竞价任务，查询报价列表
        if ($taskOrder['order_type'] == OrderConst::TYPE_COMPETITION) {
            $taskOrder['quoted_list'] =  $detailTaskOrderService->getOrderQuotedList($orderNo);
        }

        // 获取任务时间线
        $taskOrder['time_lines'] = $detailTaskOrderService->getOrderTimeLines($orderNo, $taskOrder);

        // 支付流水
        $taskOrder['pay_logs'] = $this->getPayTaskOrderService()->getPayLogByBizNo($orderNo);

        return success($taskOrder);
    }

    /**
     * @return DetailTaskOrderService
     */
    protected function getDetailTaskOrderService()
    {
        return new DetailTaskOrderBridge(new DetailTaskOrderService());
    }

    /**
     * @return CompensateService
     */
    protected function getCompensateService()
    {
        return new CompensateBridge(new CompensateService());
    }

    /**
     * @return CommentService
     */
    protected function getCommentService(){
        return new CommentBridge(new CommentService());
    }

    /**
     * @return PayTaskOrderService
     */
    protected function getPayTaskOrderService()
    {
        return new PayTaskOrderBridge(new PayTaskOrderService());
    }
}
