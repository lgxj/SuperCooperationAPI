<?php


namespace App\Http\Controllers\Trade\Order;


use App\Consts\MessageConst;
use App\Consts\Trade\OrderConst;
use App\Consts\Trade\PayConst;
use App\Http\Controllers\Controller;
use App\Services\Trade\Fund\CompensateService;
use App\Services\Trade\Order\CommentService;
use App\Services\Trade\Order\Employer\DetailTaskOrderService;
use App\Services\Trade\Order\Helper\ListOrderService;
use Illuminate\Http\Request;

class IndexController extends Controller
{

    protected $detailOrderService = null;

    public function __construct(DetailTaskOrderService $detailOrderService)
    {
        $this->detailOrderService = $detailOrderService;
    }

    public function detail(Request $request)
    {
        $orderNo = $request->get('order_no','');
        if(empty($orderNo)){
            return fail([],'任务不能为空');
        }
        $withText = $request->get('with_text',false);
        $withService = $request->get('with_service',false);
        $withOrderAddress = $request->get('with_order_address',false);
        $priceChangeType = $request->get('price_change_type',false);
        $withUser = $request->get('with_user',false);
        $taskOrder = $this->detailOrderService->getOrder($orderNo,$priceChangeType,$withText,$withService,$withOrderAddress,$withUser,getLoginUserId());
        $taskOrder['helper_comments'] = null;
        $taskOrder['defer'] = [];
        if(isset($taskOrder['order_state']) && $taskOrder['order_state'] == OrderConst::EMPLOYER_STATE_COMPLETE){
            $commentService = $this->getCommentService();
            $list = $commentService->getOrderComment($orderNo,MessageConst::TYPE_COMMENT_TASK_EMPLOYER,1);
            $taskOrder['helper_comments'] = $list[0] ?? null;
        }
        if(isset($taskOrder['order_state']) && $taskOrder['order_state'] == OrderConst::EMPLOYER_STATE_RECEIVE){
            $defer = $this->detailOrderService->getOrderDefersByOrderNo($orderNo,$taskOrder['helper_user_id']);
            $defer = $defer ? $defer->toArray() : [];
            if($defer){
                $taskOrder['defer']['defer_minutes'] = $defer['defer_minutes'];
                $taskOrder['defer']['status'] = $defer['status'];

            }
        }
        $taskOrder['compensate_price'] = 0;
        if(isset($taskOrder['order_state']) && $taskOrder['order_state'] == OrderConst::EMPLOYER_STATE_CANCEL && $this->getUserId() == $taskOrder['helper_user_id']){
            $compensate = new CompensateService();
            $cancelCompensate = $compensate->getUserCompensateByToUserId($orderNo, $taskOrder['helper_user_id'], PayConst::INOUT_EMPLOYER_COMPENSATE);
            if ($cancelCompensate) {
                $taskOrder['compensate_price'] =  display_price($cancelCompensate['compensate_price']);
            }
        }
        if(isset($taskOrder['order_state']) && $taskOrder['order_type'] == OrderConst::TYPE_COMPETITION){
           $receiver =  (new ListOrderService())->getReceiveByOrderNo($orderNo,$this->getUserId());
           if($receiver && $receiver['quoted_price'] > 0){
               //竞价订单，只有自己看底部价格
               $priceDesc = display_price($receiver['quoted_price']);
               $taskOrder['display_origin_price'] = $priceDesc;
               $taskOrder['display_pay_price'] = $priceDesc;
           }else if($this->getUserId() != $taskOrder['user_id']){

               $taskOrder['display_origin_price'] = 0;
               $taskOrder['display_pay_price'] = 0;
           }

        }
        return success($taskOrder);
    }


    public function getOrderQuotedList(Request $request){
        $orderNo = $request->get('order_no','');
        $page = $request->get('page',1);

        if(empty($orderNo)){
            return fail([],'任务编号不能为空');
        }
        $quotedList = $this->detailOrderService->getOrderQuotedList($orderNo,$page,$this->getUserId());
        return success($quotedList);
    }


    public function getOrderReceiver(Request $request){
        $orderNo = $request->get('order_no','');
        if(empty($orderNo)){
            return fail([],'任务编号不能为空');
        }
        $receiver = $this->detailOrderService->getReceiver($orderNo);
        return success($receiver);
    }

    public function getCategoryList(Request $request){
       $categoryList = $this->detailOrderService->getCategoryList();
       return success($categoryList);
    }

    /**
     * 只可添加，不可删除
     * @return array
     */
    public function config(){
            $config = [
                    'publish_pic_num' => [
                        'value'=>3,
                        'desc' => '图片限止个数'
                    ],
                    'publish_video_num' => [
                        'value'=>1,
                        'desc' => '视频个数'
                    ],
                    'publish_address' => [
                        'value'=>2,
                        'desc' => '服务地址个数'
                    ],
                    'publish_competition_price' => [
                        'value'=>OrderConst::TYPE_COMPETITION_LOW_PRICE,
                        'desc' => '竞价订单保证金'
                    ],
                    'publish_lowest_price' => [
                        'value'=>OrderConst::TYPE_GENERAL_LOW_PRICE,
                        'desc' => '普通订单最低支付价'
                    ],
                    'publish_start_time_diff_now' => [
                        'value'=>30,
                        'desc' => '开始时间要大于当前时间多少'
                    ],
                    'publish_start_time_diff_end' => [
                        'value'=>60,
                        'desc' => '开始结束时间最少相差多少'
                    ],
                    'publish_urgent_price' => [
                        'value'=>2,
                        'desc' => '加急，单位元'
                    ],
                    'publish_face_price' => [
                        'value'=>2,
                        'desc' => '人脸接单，单位元'
                    ],
                    'publish_insurance_price' => [
                        'value'=>5,
                        'desc' => '保险，单位元'
                    ],
                    'pay_channels' => PayConst::getChannelList(),
                    'task_category'=> $this->detailOrderService->getCategoryList(),
                    'employer_cancel_type' => OrderConst::getEmployerCancelTypeList(),
                    'helper_cancel_type' => OrderConst::getHelperCancelTypeList(),
                    'employer_refuse_type' => OrderConst::getEmployerRefuseTypeList(),
                    'pages' => [
                        'about_us' => 'https://sc.250.cn/content/article/detail?id=1',
                        'privacy_policy' => 'https://sc.250.cn/content/article/detail?id=2',
                        'user_agreement' => 'https://sc.250.cn/content/article/detail?id=3',
                        'helper_center' => 'https://sc.250.cn/content/article/detail?id=4',
                        'problem' => 'https://sc.250.cn/content/article/detail?id=5'
                    ],
                    'distance' => 10
                ];

            return success($config);

    }

    protected function getCommentService(){
        return new CommentService();
    }
}
