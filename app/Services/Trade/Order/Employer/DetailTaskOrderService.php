<?php


namespace App\Services\Trade\Order\Employer;


use App\Bridges\User\CertificationBridge;
use App\Consts\GlobalConst;
use App\Consts\MessageConst;
use App\Consts\Trade\OrderConst;
use App\Consts\Trade\PayConst;
use App\Consts\UserConst;
use App\Models\Trade\Order\Address;
use App\Models\Trade\Order\Defer;
use App\Models\Trade\Order\PriceChange;
use App\Models\Trade\Order\Service;
use App\Models\Trade\Order\StateChange;
use App\Models\Trade\Order\TaskOrder;
use App\Models\Trade\Order\Text;
use App\Models\User\DeliveryRecord;
use App\Services\Trade\Order\BaseTaskOrderService;
use App\Services\Trade\Order\CommentService;
use App\Services\Trade\Order\Helper\ListOrderService;
use App\Services\User\CertificationService;
use Illuminate\Support\Collection;

/**
 * 任务详情
 *
 * Class DetailTaskOrderService
 * @package App\Services\Trade\Order\Employer
 */
class DetailTaskOrderService extends BaseTaskOrderService
{

    /**
     * 获取任务单信息
     *
     * @param array $orderNos 订单号列表
     * @param string $mainPriceChangeType 价格支付类型
     * @param bool $withText 是否获取文本信息
     * @param bool $withService 是否获取服务
     * @param bool $withOrderAddress 是否获取地址
     * @param bool $withUser 是否获取用户信息
     * @param int $userId
     * @return array|Collection
     */
    public function getOrders(array $orderNos, $mainPriceChangeType = 'pay_price', bool $withText = false, bool $withService = false, $withOrderAddress = false, $withUser = false,$userId = 0){
        if(empty($orderNos)){
            return [];
        }
        $orders = TaskOrder::whereIn('order_no',$orderNos)->get()->keyBy('order_no');
        if(empty($orders)){
            return [];
        }
        $texts = [];
        $services = [];
        $addressList = [];
        $users  = [];
        if($withText){
            $texts = $this->getTextByOrderNos($orderNos);
        }
        if($withService) {
            $services = $this->getServiceByOrderNos($orderNos);
        }

        if($withOrderAddress){
            $addressList = $this->getAddressByOrderNos($orderNos);
        }

        if($withUser){
            $userIds = array_keys($orders->keyBy('user_id')->toArray());
            $userService = $this->getUserService();
            $users = $userService->users($userIds);
        }

        $categoryList = $this->getCategoryKV();
        $orders = $orders->map(function ($order,$orderNo) use ($texts,$services,$mainPriceChangeType,$addressList,$users,$categoryList){
            $orderArray = $order->toArray();
            $user = $users[$orderArray['user_id']] ?? [];
            unset($user['pay_password']);
            $orderServices = $services[$orderNo] ??  [];
            $orderServicesAlias = $this->buildOrderService($orderArray['order_state'],$orderServices);
            $orderText =  $texts[$orderNo] ?? [];
            $orderArray['services'] = $orderServicesAlias;
            $orderArray['text'] = $orderText;
            $orderArray['address_list'] = $addressList[$orderNo] ?? [];
            $orderArray['user'] = $user;
            $orderArray['order_category_desc'] = $categoryList[$orderArray['category']] ?? [];
            $orderArray['price_change'] = [];
            $mainPriceChangeTypeList = OrderConst::getMainPriceChangeTypeList();
            if(in_array($mainPriceChangeType, $mainPriceChangeTypeList)) {
                $mainPriceChangeType = array_flip($mainPriceChangeTypeList)[$mainPriceChangeType];
                $priceChange = $this->getLatestChangePayPrice($orderNo,$mainPriceChangeType);
                $orderArray['price_change'] = $priceChange;
            }
            return $orderArray;
        })->toArray();
        $this->formatOrder($orders,$userId);
        return $orders;
    }

    public function getOrder($orderNo,$mainPriceChangeType = 'pay_price',bool $withText = false,bool $withService = false,$withOrderAddress = false,$withUser = false,$userId = 0){
       $orders = $this->getOrders([$orderNo],$mainPriceChangeType,$withText,$withService,$withOrderAddress,$withUser,$userId);
       return $orders[$orderNo] ?? [];
    }

    public function getOrderQuotedList($orderNo,$page=1,$_userId = 0){
        $receiver = $this->getReceiveModel();
        $orderQuotedList = $receiver->getOrderQuotedList($orderNo)->forPage($page,GlobalConst::PAGE_SIZE)->keyBy('user_id');
        if(empty($orderQuotedList->toArray())){
            return [];
        }
        $userService = $this->getUserService();
        $userIds = $orderQuotedList->pluck('user_id')->toArray();
        $users = $userService->users($userIds);
        $faceAuths = $this->getCertificationBridge()->getFaceAuthByUserIds($userIds,$orderNo,UserConst::FACE_AUTH_TYPE_RECEIVE);
        $formatQuotedList = $orderQuotedList->map(function ($quoted,$userId) use ($users,$faceAuths,$_userId){
           $quotedArray = $quoted->toArray();
           $user = $users[$userId] ?? [];
           $faceAuth = $faceAuths[$userId] ?? null;
           $quotedArray['face_auth'] = $faceAuth;
           $quotedArray['is_self'] = ($userId == $_userId);
           format_receiver_order($quotedArray,$user);
           return $quotedArray;
        });
       return $formatQuotedList->toArray();
    }

    /**
     * 任务时间线
     * @param $orderNo
     * @param $order
     * @return array
     */
    public function getOrderTimeLines($orderNo, $order)
    {
        $lines = [];

        // 创建任务
        $lines[] = [
            'content' => '创建任务',
            'timestamp' => $order['created_at'],
            'type' => 'primary'
        ];

        // 雇主任务状态变化
        $statsChange = StateChange::where('order_no', $orderNo)->where('user_type', 0)->orderByDesc('state_change_id')->get()->toArray();
        $users = [];
        foreach ($statsChange as $item) {
            $line = [];
            switch ($item['state']) {
                case OrderConst::EMPLOYER_STATE_UN_RECEIVE: // 待帮手接单
                case OrderConst::EMPLOYER_STATE_UN_CONFIRM: // 待帮手竞价
                    if ($item['before_state'] == OrderConst::EMPLOYER_STATE_UN_START) {   // 待支付
                        $line = [
                            'content' => '雇主支付订单',
                            'timestamp' => $item['created_at'],
                            'type' => 'primary'
                        ];
                    } else { // 订单取消
                        $user = $this->_getUser($item['current_user_id'], $users);
                        $line = [
                            'content' => '帮手【' . ($user['user_name'] ?? '') . '】取消任务',
                            'timestamp' => $item['created_at'],
                            'type' => 'danger'
                        ];
                    }
                    break;
                case OrderConst::EMPLOYER_STATE_RECEIVE: // 已接单
                    if ($item['before_state'] == OrderConst::EMPLOYER_STATE_UN_RECEIVE) {   // 待帮手接单
                        $user = $this->_getUser($item['current_user_id'], $users);
                        $line = [
                            'content' => '帮手【' . ($user['user_name'] ?? '') . '】接单',
                            'timestamp' => $item['created_at'],
                            'type' => 'primary'
                        ];
                    } else if ($item['before_state'] == OrderConst::EMPLOYER_STATE_UN_CONFIRM) { // 待帮手竞价
                        $line = [
                            'content' => '雇主选择报价',
                            'timestamp' => $item['created_at'],
                            'type' => 'primary'
                        ];
                    }
                    break;
                case OrderConst::EMPLOYER_STATE_DELIVERED: // 已交付
                    $user = $this->_getUser($item['current_user_id'], $users);
                    if ($item['before_state'] == OrderConst::EMPLOYER_STATE_RECEIVE) {   // 已接单
                        $line = [
                            'content' => '帮手【' . ($user['user_name'] ?? '') . '】交付任务',
                            'timestamp' => $item['created_at'],
                            'type' => 'primary'
                        ];
                    } else if ($item['before_state'] == OrderConst::EMPLOYER_STATE_REFUSE_DELIVERY) {   // 拒绝交付
                        $line = [
                            'content' => '帮手【' . ($user['user_name'] ?? '') . '】再次交付任务',
                            'timestamp' => $item['created_at'],
                            'type' => 'primary'
                        ];
                    }
                    break;
                case OrderConst::EMPLOYER_STATE_CANCEL: // 已取消
                    if ($item['user_id'] == $item['current_user_id']) {  // 雇主取消
                        $line = [
                            'content' => '雇主取消任务',
                            'timestamp' => $item['created_at'],
                            'type' => 'danger'
                        ];
                    } else if ($item['current_user_id']) {   // 帮手取消
                        $user = $this->_getUser($item['current_user_id'], $users);
                        $line = [
                            'content' => '帮手【' . ($user['user_name'] ?? '') . '】取消任务',
                            'timestamp' => $item['created_at'],
                            'type' => 'danger'
                        ];
                    } else {
                        $line = [
                            'content' => '系统取消任务',
                            'timestamp' => $item['created_at'],
                            'type' => 'danger'
                        ];
                    }
                    break;
                case OrderConst::EMPLOYER_STATE_COMPLETE: // 已完成
                    $line = [
                        'content' => '雇主同意交付任务',
                        'timestamp' => $item['created_at'],
                        'type' => 'success'
                    ];
                    break;
                case OrderConst::EMPLOYER_STATE_REFUSE_DELIVERY: // 拒绝交付
                    $line = [
                        'content' => '雇主拒绝交付任务',
                        'timestamp' => $item['created_at'],
                        'type' => 'warning'
                    ];
                    break;
            }

            if (!empty($line)) {
                $lines[] = $line;
            }
        }

        // 报价记录
        if (isset($order['quoted_list']) && $order['quoted_list']) {
            foreach ($order['quoted_list'] as $item) {
                $user = $this->_getUser($item['user_id'], $users);
                $lines[] = [
                    'content' => '帮手【' . $user['user_name'] . '】报价',
                    'timestamp' => $item['created_at'],
                    'type' => 'primary'
                ];
            }
        }

        // 评价
        if ($order['helper_comments']) {
            $lines[] = [
                'content' => '帮手评价',
                'timestamp' => $order['helper_comments']['created_at'],
                'type' => 'primary'
            ];
        }
        if ($order['employer_comments']) {
            $lines[] = [
                'content' => '雇主评价',
                'timestamp' => $order['helper_comments']['created_at'],
                'type' => 'primary'
            ];
        }

        // 按时间排序
        usort($lines, function ($a, $b) {
            $t1 = $a['timestamp'];
            $t2 = $b['timestamp'];
            if ($t1 == $t2) return 0;
            return $t1 > $t2 ? 1 : -1;
        });

        return $lines;
    }

    private function _getUser($user_id, &$users)
    {
        if (!$user_id) return [];
        if (isset($users[$user_id])) return $users[$user_id];
        $userService = $this->getUserService();
        $user = $userService->user($user_id);
        $users[$user_id] = $user;
        return $user;
    }

    public function getOrderDefersByOrderNos(array $orderNos){
        if(empty($orderNos)){
            return [];
        }
        return Defer::whereIn('order_no',$orderNos)->get()->keyBy('order_no')->toArray();
    }

    public function getOrderDefersByOrderNo($orderNo,$userId){
        if(empty($orderNo) || $userId <= 0){
            return [];
        }
        return   Defer::where(['order_no'=>$orderNo,'user_id'=>$userId])->first();
    }
    public function getValidReceiverList(array $orderNos){
        if(empty($orderNos)){
            return [];
        }
        $receiver = $this->getReceiveModel();
        $validReceiverList = $receiver->getValidReceiverByOrderNos($orderNos)->keyBy('user_id');
        if(empty($validReceiverList->toArray())){
            return [];
        }
        $orders = TaskOrder::getModel()->getByOrderNos($orderNos);
        $userService = $this->getUserService();
        $userIds = $validReceiverList->pluck('user_id')->toArray();
        $users = $userService->users($userIds);
        $formatReceivedList = $validReceiverList->map(function ($quoted,$userId) use ($users,$orders){
            $orderReceiver = $quoted->toArray();
            $orderNo = $orderReceiver['order_no'];
            $taskOrder = $orders[$orderNo] ?? null;
            $user = $users[$userId] ?? [];
            format_receiver_order($orderReceiver,$user,$taskOrder->toArray());
            return $orderReceiver;
        });
        return $formatReceivedList;
    }

    public function getReceiver($orderNo, $isRealName = false){
        if(!trim($orderNo)){
            return fail([]);
        }
        $receiverGroups =  (new ListOrderService())->getReceivesByOrderNos([$orderNo]);
        $receivers = $receiverGroups[$orderNo] ?? [];
        if(empty($receivers)){
            return [];
        }
        $taskOrder = TaskOrder::getModel()->getByOrderNo($orderNo);
        $receivers = collect($receivers)->keyBy('user_id')->toArray();
        $receiver = isset($receivers[getLoginUserId()]) ? $receivers[getLoginUserId()] : array_pop($receivers);
        $userService = $this->getUserService();
        $user = $userService->user($receiver['user_id'], $isRealName);
        format_receiver_order($receiver,$user,$taskOrder->toArray());
        $receiver['compensate_price'] = 0;
        if($receiver['is_self'] && $receiver['cancel_compensate_status'] > 0){
            $compensatePriceList = $this->getDetailService()->getLatestChangePayPrice($orderNo, OrderConst::PRICE_CHANGE_HELPER_CANCEL, true);
            $compensatePrice = array_sum(array_values($compensatePriceList));
            $receiver['compensate_price'] = $compensatePrice;
        }
        $faceAuth = $this->getCertificationBridge()->isFaceAuth($receiver['user_id'],$orderNo,UserConst::FACE_AUTH_TYPE_RECEIVE);
        $receiver['face_auth'] = $faceAuth ? $faceAuth->toArray() : null;
        $receiver['receive_comment_id'] = $receiver['comment_id'] ?? 0;
        $receiver['employer_comments'] = null;
        $receiver['latest_refused'] = null;
        if(isset($taskOrder['order_state']) && $taskOrder['order_state'] == OrderConst::EMPLOYER_STATE_COMPLETE){
            $commentService = $this->getCommentService();
            $list = $commentService->getOrderComment($orderNo,MessageConst::TYPE_COMMENT_TASK_HELPER,1);
            $receiver['employer_comments'] = $list[0] ?? null;
        }
        if(isset($taskOrder['order_state']) && $taskOrder['order_state'] == OrderConst::EMPLOYER_STATE_REFUSE_DELIVERY){
            $deliveryRecordModel = new DeliveryRecord();
            $deliveryRecord = $deliveryRecordModel->getLatestRecord($receiver['user_id'],$orderNo);
            if($deliveryRecord) {
                $deliveryRecord = $deliveryRecord->toArray();
                $deliveryRecord['refuse_type_desc'] = OrderConst::getEmployerRefuseTypeList($deliveryRecord['refuse_type']);
                $receiver['latest_refused'] = $deliveryRecord;
            }
        }
        return $receiver;
    }

    public function getTextByOrderNos(array $orderNos){
        if(empty($orderNos)){
            return [];
        }
        return Text::whereIn('order_no',$orderNos)->get()->keyBy('order_no')->toArray();
    }

    public function getServiceByOrderNos(array $orderNos){
        if(empty($orderNos)){
            return [];
        }
        return Service::whereIn('order_no',$orderNos)->get()->groupBy('order_no')->toArray();
    }

    public function getAddressByOrderNos(array $orderNos){
        if(empty($orderNos)){
            return [];
        }
        return Address::whereIn('order_no',$orderNos)->get()->groupBy('order_no')->toArray();
    }

    public function getEnableServiceByOrderNo($orderNo){
        if(empty($orderNo)){
            return [];
        }
        return Service::where(['order_no'=>$orderNo,'pay_state'=>PayConst::STATE_PAY])->get()->pluck('service_price','service_type')->toArray();
    }

    public function sumEnableServiceByOrderNo($orderNo,array $serviceTypes = []){
        if(empty($orderNo)){
            return 0;
        }
        return $this->getServiceModel()->sumEnableServiceByOrderNo($orderNo,$serviceTypes);
    }

    public function geServicesByOrderNo($orderNo){
        if(empty($orderNo)){
            return [];
        }
        return Service::where(['order_no'=>$orderNo])->get()->pluck('service_price','service_type')->toArray();
    }

    public function getOrderPriceListChangeByType($taskOrderNo,$priceChangeType = OrderConst::PRICE_CHANGE_ORDER_PAY){
        return PriceChange::where(['order_no'=>$taskOrderNo,'change_type'=>$priceChangeType])->orderByDesc('price_change_id')->take(50)->get()->toArray();
    }


    public function getOrderPriceListByWaterNo($orderNo,$waterNo){
        return PriceChange::where(['order_no'=>$orderNo,'water_no'=>$waterNo])->get()->toArray();
    }

    public function getOrderPriceListWithWaterNo($waterNo){
        return PriceChange::where(['water_no'=>$waterNo])->get()->toArray();
    }

    public function getLatestOrderPriceChangeType($taskOrderNo,$priceChangeType = OrderConst::PRICE_CHANGE_ORDER_PAY){
        $data = PriceChange::where(['order_no'=>$taskOrderNo,'change_type'=>$priceChangeType])->orderByDesc('price_change_id')->first();
        return $data ? $data->toArray() : $data;
    }

    public function getOrderPriceChangeFirstPay($taskOrderNo){
        $data = PriceChange::where(['order_no'=>$taskOrderNo,'change_type'=>OrderConst::PRICE_CHANGE_ORDER_PAY,'op_state'=>OrderConst::PRICE_OP_STATE_PAY])->first();
        return $data ? $data->toArray() : $data;
    }

    public function getLatestOrderPriceChangeWithNumberKey($taskOrderNo, $priceChangeType = OrderConst::PRICE_CHANGE_ORDER_PAY){
        $priceChange =  $this->getOrderPriceListChangeByType($taskOrderNo,$priceChangeType);
        if(empty($priceChange)){
            return [];
        }
        collect($priceChange)->each(function ($item,$waterNo) {
            static $counter = 1;
            if($counter > 2){
                $this->getPayService()->deleteUnHandleChangePriceByWaterNo($item['water_no']);
            }
            $counter++;
        });
        $latest = $priceChange[0];
        $priceTypeList = [];
        $orderNo = $latest['order_no'];
        $waterNo = $latest['water_no'];
        $latestAll = $this->getOrderPriceListByWaterNo($orderNo,$waterNo);
        collect($latestAll)->each(function ($samePriceChange) use(&$priceTypeList){
            $priceTypeList[$samePriceChange['change_type']] = $samePriceChange['price'];
        });
        return $priceTypeList;
    }


    public function deleteUnPayService($orderNo){
        return Service::where(['order_no'=>$orderNo,'pay_state'=>PayConst::STATE_UN_PAY])->delete();
    }



    public function getLatestChangePayPrice($orderNo,$priceChangeType = OrderConst::PRICE_CHANGE_ORDER_PAY,$isFormat = false)
    {
       $latestPriceChange =  $this->getLatestOrderPriceChangeType($orderNo,$priceChangeType);
       if(empty($latestPriceChange)) {
           return [];
       }
       $latestAllPrice = $this->getOrderPriceListByWaterNo($latestPriceChange['order_no'],$latestPriceChange['water_no']);
       $payPrice = [];
       $identifyList = OrderConst::getChangePriceIdentifyList();
       $mainChangeTypeList = array_keys(OrderConst::getMainPriceChangeTypeList());
       collect($latestAllPrice)->each(function ($item) use (&$payPrice,$identifyList,$mainChangeTypeList,$isFormat){
           $identify = $identifyList[$item['change_type']];
           if(in_array($item['change_type'],$mainChangeTypeList)){
               $payPrice['pay_price'] =  $isFormat ? display_price($item['price']) : $item['price'];
           }else {
               $payPrice[$identify] = $isFormat ? display_price($item['price']) : $item['price'];;
           }
       });
       return $payPrice;
    }

    public function getChangePriceByWaterNo($waterNo){
        $priceChangeList = $this->getOrderPriceListWithWaterNo($waterNo);
        if(empty($priceChangeList)){
            return [];
        }
        $payPrice = [];
        $identifyList = OrderConst::getChangePriceIdentifyList();
        $mainChangeTypeList = array_keys(OrderConst::getMainPriceChangeTypeList());
        $mainType = 0;
        collect($priceChangeList)->each(function ($item) use (&$payPrice,&$mainType,$identifyList,$mainChangeTypeList){
            $identify = $identifyList[$item['change_type']];
            if(in_array($item['change_type'],$mainChangeTypeList)){
                $mainType = $item['change_type'];
                $payPrice['pay_price'] =  $item['price'];
            }else {
                $payPrice[$identify] = $item['price'];
            }
        });
        return ['main_pay_type'=>$mainType,'pay_price_list'=>$payPrice];
    }

    protected function buildOrderService( $orderState,array $services){
        $identifyList = OrderConst::getServiceTypeList();
        $servicesAlias = [];
        collect($identifyList)->each(function ($identify,$index) use (&$servicesAlias){
            $servicesAlias[$identify] = 0;
        });
        if(empty($services)){
            return $servicesAlias;
        }
        $orderServices = collect($services)->keyBy('service_type');
        $orderServices->each(function ($service,$serviceType)use($orderState,$identifyList,&$servicesAlias){
            $orderIsStart = ($orderState > OrderConst::EMPLOYER_STATE_UN_START);
            $isPay = ($service['pay_state'] == PayConst::STATE_PAY);
            $servicesAlias[$identifyList[$service['service_type']]] = (($orderIsStart && $isPay) || !$orderIsStart) ?  $service['service_price'] : 0;
        });
        return $servicesAlias;
    }

    /**
     * 订单新增、修改时查最后一次价格变更
     *
     * @param $orderNo
     * @param $orderState
     * @param $orderType
     * @return mixed
     */
    protected function getOrderLatestPriceChangeWithModify($orderNo,$orderState,$orderType){
        $priceChange['latest_price_pay'] = [];
        $priceChange['latest_price_makeup'] = [];
        $priceChange['latest_price_confirm'] = [];

        /**
         * 首次修改或改价
         */
        if(OrderConst::EMPLOYER_STATE_UN_START == $orderState) {
            $priceChange['latest_price_pay'] = $this->getLatestChangePayPrice($orderNo,OrderConst::PRICE_CHANGE_ORDER_PAY);
        }else{
            $priceChange['latest_price_pay'] = $this->getLatestChangePayPrice($orderNo,OrderConst::PRICE_CHANGE_ORDER_PAY);
            $priceChange['latest_price_makeup'] = $this->getLatestChangePayPrice($orderNo,OrderConst::PRICE_CHANGE_MAKE_UP);
            if($orderType == OrderConst::TYPE_COMPETITION){
                $priceChange['latest_price_confirm'] = $this->getLatestChangePayPrice($orderNo,OrderConst::PRICE_CHANGE_CONFIRM);
            }
        }
        return $priceChange;
    }

    public function formatOrder(array &$orders,$userId){

        if(empty($orders)){
            return $orders;
        }
        foreach ($orders as $key=>$order){
            if($order['user_id'] == $userId && $order['services']){
                //雇主查看的是所有支付总价
                $serviceTotal =  array_sum(array_values($order['services']));
                $orders[$key]['display_origin_price'] = display_price(bcadd($order['origin_price'],$serviceTotal));
                $orders[$key]['display_pay_price'] = display_price(bcadd($order['pay_price'],$serviceTotal));
            }else {
                $orders[$key]['display_origin_price'] = display_price($order['origin_price']);
                $orders[$key]['display_pay_price'] = display_price($order['pay_price']);
            }
            $orders[$key]['display_helper_price'] = display_price($order['origin_price']);
            $orders[$key]['display_services'] = [];
            foreach ($orders[$key]['services'] as $priceName=>$price){
                $orders[$key]['display_services'][$priceName] = display_price($price);
            }
            foreach ($orders[$key]['price_change'] as $priceName=>$price){
                $orders[$key]['display_price_change'][$priceName] = display_price($price);
            }
            $orders[$key]['display_order_type'] = OrderConst::getTypeList($order['order_type']);
            $orders[$key]['display_employer_order_state'] = OrderConst::getEmployerStateList($order['order_state']);
            $orders[$key]['display_employer_pay_state'] = PayConst::getStateList($order['pay_state']);
            $orders[$key]['display_cancel_type'] = OrderConst::getCancelTypeList($order['cancel_type']);
            $orders[$key]['bottom_receive_state'] = $orders[$key]['display_employer_order_state'];
        }
        return $orders;
    }

    /**
     * @return CertificationService
     */
    protected function getCertificationBridge(){
        return new CertificationBridge(new CertificationService());
    }

    protected function getCommentService(){
        return new CommentService();
    }
}
