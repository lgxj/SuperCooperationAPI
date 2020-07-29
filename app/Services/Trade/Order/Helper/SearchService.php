<?php


namespace App\Services\Trade\Order\Helper;


use App\Bridges\Pool\AddressBridge;
use App\Consts\GlobalConst;
use App\Consts\Trade\OrderConst;
use App\Models\Trade\Order\Search;
use App\Services\Pool\AddressService;
use App\Services\Pool\YunTuService;
use App\Services\ScService;
use App\Services\Trade\Traits\ModelTrait;
use App\Services\Trade\Traits\ServiceTrait;
use App\Utils\Map\YunTu;

/**
 * 任务大厅与任务搜索
 *
 * Class SearchService
 * @package App\Services\Trade\Order\Helper
 */
class SearchService extends  ScService
{
    use ServiceTrait;
    use ModelTrait;

    const SEARCH_TYPE_BIT_URGE = 1;
    const SEARCH_TYPE_BIT_INSURANCE = 2;
    const SEARCH_TYPE_BIT_FACE = 4;

    const SEARCH_TYPE_BIT_GENERAL = 100;
    const SEARCH_TYPE_BIT_COMPETITION = 101;

    public function getSearchOptions(){
        $categoryList = $this->getDetailService()->getCategoryKV();
        return [
            'task_sort' => [
                1=>'报酬由低到高',
                2=>'报酬由高到低',
                4=>'时间由近到远',
                3=>'时间由远到近',
                0=>'不限'
            ],
            'search_type' =>[
                self::SEARCH_TYPE_BIT_URGE=> '加急任务',
                self::SEARCH_TYPE_BIT_INSURANCE=> '保险任务',
                self::SEARCH_TYPE_BIT_COMPETITION => '竞价任务',
                self::SEARCH_TYPE_BIT_GENERAL => '悬赏任务',
                self::SEARCH_TYPE_BIT_FACE=> '人脸识别',
                0 => '不限'
            ],
            'task_category' => $categoryList,
            'distance_total' => 10
        ];
    }

    /**
     * @param $orderName
     * @param $lng
     * @param $lat
     * @param $distance
     * @param array $searchTypeList
     * @param array $taskCategoryList
     * @param int $page
     * @param int $sort
     * @param int $pageSize
     * @param int $userId
     * @return array
     * @throws \App\Exceptions\BusinessException
     */
    public function search($orderName,$lng,$lat,$distance,array $searchTypeList,array $taskCategoryList,$page = 1,int $sort = 0,int $pageSize = GlobalConst::PAGE_SIZE ,$userId = 0){
        $hasUrge = [1,3,5,7];
        $hasFace = [4,5,6,7];
        $hasInsurance = [2,3,6,7];
        $orderNos = [];
        $yunTuSort = '_distance:1';
        $tencentSort = "distance({$lat},{$lng})";
        $yunTuFilter = '';
        $tencentFilter = '';
        $distance = $distance * GlobalConst::KM_TO_M;
        if($distance > 0){
            //先取出这个距离范围内的订单，托底方案
        }
        if($page <= 0){
            $page = 1;
        }
        $orderName = trim($orderName);
        $orderTypes = [];
        if(in_array(self::SEARCH_TYPE_BIT_COMPETITION,$searchTypeList)){
            $orderTypes[] = OrderConst::TYPE_COMPETITION;
        }
        if(in_array(self::SEARCH_TYPE_BIT_GENERAL,$searchTypeList)){
            $orderTypes[] = OrderConst::TYPE_GENERAL;
        }
        $serviceTypes = [];
        foreach ($searchTypeList as $type){
            if($type == self::SEARCH_TYPE_BIT_URGE){
                $serviceTypes = array_append($serviceTypes,$hasUrge);
                $yunTuFilter .= ($yunTuFilter ? '+urge:':'urge:').OrderConst::SERVICE_PRICE_TYPE_URGE;
                $tencentFilter .= ($tencentFilter ? ' and x.urge=':'x.urge=').OrderConst::SERVICE_PRICE_TYPE_URGE;
            }elseif($type == self::SEARCH_TYPE_BIT_INSURANCE){
                $serviceTypes = array_append($serviceTypes,$hasInsurance);
                $yunTuFilter .= ($yunTuFilter ? '+insurance:':'insurance:').OrderConst::SERVICE_PRICE_TYPE_INSURANCE;
                $tencentFilter .= ($tencentFilter ? ' and x.insurance=':'x.insurance=').OrderConst::SERVICE_PRICE_TYPE_INSURANCE;
            }elseif($type == self::SEARCH_TYPE_BIT_FACE){
                $serviceTypes = array_append($serviceTypes,$hasFace);
                $yunTuFilter .= ($yunTuFilter ? '+face:':'face:').OrderConst::SERVICE_PRICE_TYPE_FACE;
                $tencentFilter .= ($tencentFilter ? ' and x.face=':'x.face=').OrderConst::SERVICE_PRICE_TYPE_INSURANCE;
            }
        }

        $serviceTypes = array_unique($serviceTypes);
        $searchModel = Search::when(!empty($orderName),function ($query) use($orderName){
            $query->where('order_name','like',"{$orderName}%");
        })->when(!empty($orderNos),function ($query) use($orderNos){
            $query->whereIn('order_no',$orderNos);
        })->when(!empty($taskCategoryList),function ($query) use($taskCategoryList,&$yunTuFilter,&$tencentFilter){
            $query->whereIn('category',$taskCategoryList);
            $yunTuFilter .= $yunTuFilter ? "+category:{$taskCategoryList[0]}" : "category:{$taskCategoryList[0]}";
            $tencentFilter .= $tencentFilter ? " and x.category={$taskCategoryList[0]}" : "x.category={$taskCategoryList[0]}";
        })->when($orderTypes && count($orderTypes) < 2,function ($query) use($orderTypes,&$yunTuFilter,&$tencentFilter){
            //$orderTypes个数等于2就是全部类型订单了
            $query->where('order_type',$orderTypes[0]);
            $yunTuFilter .= $yunTuFilter ? "+order_type:{$orderTypes[0]}" : "order_type:{$orderTypes[0]}";
            $tencentFilter .= $tencentFilter ? " and x.order_type={$orderTypes[0]}" : "x.order_type={$orderTypes[0]}";
        })->when(!empty($serviceTypes),function ($query)use($serviceTypes,&$yunTuFilter){
            $query->whereIn('service_type',$serviceTypes);
        })->when($sort > 0 ,function ($query) use ($sort,&$yunTuSort,&$tencentSort){
            $yunTuSort = "_distance:1";
            if($sort == 1){
                $query->orderBy('pay_price','asc');
                $yunTuSort = 'pay_price:1';
                $tencentSort = 'x.pay_price asc';
            }elseif($sort == 2){
                $query->orderBy('pay_price','desc');
                $yunTuSort = 'pay_price:0';
                $tencentSort = 'x.pay_price desc';
            }elseif($sort == 3){
                $query->orderBy('created_at','asc');
                $yunTuSort = '_createtime:1';
                $tencentSort = 'x.created_at asc';
            }elseif($sort == 4){
                $query->orderBy('created_at','desc');
                $yunTuSort = '_createtime:0';
                $tencentSort = 'x.created_at desc';
            }
        })->select('order_no');
        if(empty($lng) && empty($lat)){
            //经纬度为空，走本地数据库搜索
           $list  =  $searchModel->forpage($page,$pageSize)->get();
           $orderNos = $list->pluck('order_no')->toArray();
           $list = $list->toArray();
           foreach ($list as $key=>$searchOrder){
               $list[$key]['business_no'] = $searchOrder['order_no'];
           }
        }else {
            $yunTuUtil = getYunTu();
            $yunTuService = new YunTuService();
            $tableId = $yunTuService->getEmployerTableId();
            $amap = config('map.enable','amap');
            if($amap == 'amap') {
                list($list, $count) = $yunTuUtil->aroundSearch($tableId, $lng, $lat, $distance, $yunTuFilter, $orderName, $yunTuSort, $page, $pageSize);
            }else{
                list($list, $count) = $yunTuUtil->aroundSearch($tableId, $lng, $lat, $distance, $tencentFilter, $orderName, $tencentSort, $page, $pageSize);
            }
            $list = collect($list);
            $orderNos = $list->pluck('business_no')->toArray();
        }

        if(empty($orderNos)){
            return [];
        }
        $orders = $this->getDetailService()->getOrders($orderNos,'',false,true,true,true);
        $return = [];
        $loginUserId = getLoginUserId();
        $receiverOrders = (new ListOrderService())->getUserByOrderNos($loginUserId,$orderNos);
        foreach ($list as $yunTuOrder){
            $order = $orders[$yunTuOrder['business_no']] ?? [];
            if(empty($order)){
               continue;
            }
            $validTime = valid_between_time($order['start_time'],$order['end_time']);
            $taskTimeDesc = format_time_by_minute($validTime['diff_minutes'],$validTime['status']);
            $receiverOrder = $receiverOrders[$order['order_no']] ?? [];
            $tmp = format_task_order($order,$yunTuOrder,$userId);
            $tmp['bottom_receive_state'] = '';
            $tmp['receive_state'] = $receiverOrder['receive_state'] ?? 0;
            if($tmp['order_type'] == OrderConst::TYPE_COMPETITION){
                $quoted_price = display_price($receiverOrder['quoted_price'] ?? 0);
                if($receiverOrder) {
                    $tmp['bottom_receive_state'] =  "报价{$quoted_price}元," . OrderConst::getHelperStateList($receiverOrder['receive_state']);
                }else{
                    $tmp['bottom_receive_state'] = $taskTimeDesc ? $taskTimeDesc : '等待帮手竞价';
                }
            }
            $return[] = $tmp;
        }
        return $return;
    }

    public function saveOrderSearch($orderNo){
        $searchModel = $this->getOrderSearchModel();
        $taskOrder = $this->getTaskOrderModel()->getByOrderNo($orderNo);
        $bitService = $this->getEnableBitMapService($orderNo);
        $search = $searchModel->find($orderNo);
        if(empty($search)){
            $search = $searchModel;
        }
        $addressList = $this->getDetailService()->getAddressByOrderNos([$orderNo]);
        $address = $addressList[$orderNo][0] ?? [];
        $addressLibrary = $this->getAddressBridge()->getById($address['address_id'] ?? 0);
        $data['order_no'] = $taskOrder['order_no'];
        $data['order_name'] = $taskOrder['order_name'];
        $data['order_type'] = $taskOrder['order_type'];
        $data['category'] = $taskOrder['category'];
        $data['pay_price'] = $taskOrder['origin_price'];
        $data['created_at'] = $taskOrder['created_at'];
        $data['service_type'] = array_sum($bitService);
        $data['city_id'] = $addressLibrary['gov_area_id'] ?? 0;
        $fields = $search->getTableColumns();
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $search->$field = $data[$field];
            }
        }
        $search->save();
        return $search->toArray();
    }

    /**
     * @param $orderNo
     * @return bool
     */
    public function deleteOrderSearch($orderNo){
        return $this->getOrderSearchModel()->where(['order_no'=>$orderNo])->delete();
    }


    public function serviceBitMap($serviceType = null){
        $list = [
            OrderConst::SERVICE_PRICE_TYPE_URGE => self::SEARCH_TYPE_BIT_URGE,
            OrderConst::SERVICE_PRICE_TYPE_INSURANCE => self::SEARCH_TYPE_BIT_INSURANCE,
            OrderConst::SERVICE_PRICE_TYPE_FACE => self::SEARCH_TYPE_BIT_FACE
        ];
        return !is_null($serviceType) ? $list[$serviceType] ?? 0 : $list;
    }

    public function getEnableBitMapService($orderNo){
        $services = $this->getDetailService()->getEnableServiceByOrderNo($orderNo);
        $bitService = [];
        $bitMaps = $this->serviceBitMap();
        foreach ($services as $type=>$price){
            if(isset($bitMaps[$type])){
                $bitService[] = $bitMaps[$type];
            }
        }
        return $bitService;
    }

    public function searchHelper($lng,$lat,$distance,int $page = 1,int $pageSize = 99){
        $yunTuUtil = getYunTu();
        $distance = $distance * GlobalConst::KM_TO_M;
        $yunTuService = new YunTuService();
        $tableId = $yunTuService->getHelperTableId();
        $yunTuSort = '_distance:1';
        $tencentSort = "distance({$lat},{$lng})";
        $return = [
            'list' => [],
            'count' => 0
        ];
        $amap = config('map.enable','amap');
        if($amap == 'amap') {
            list($list, $count) = $yunTuUtil->aroundSearch($tableId, $lng, $lat, $distance, '', '', $yunTuSort, $page, $pageSize);
        }else{
            list($list, $count) = $yunTuUtil->aroundSearch($tableId, $lng, $lat, $distance, '', '', $tencentSort, $page, $pageSize);
        }
        if(empty($list)){
            return $return;
        }
        $list = collect($list);
        $userIds = $list->pluck('business_no')->toArray();
        $list = $list->keyBy('business_no')->toArray();
        $users = $this->getUserService()->users($userIds);
        $return = [];
        foreach ($users as $userId=>$user){
            $yunTuOrder = $list[$userId] ?? [];
            $tmp = $yunTuOrder;
            $tmp['user_name'] = $user['user_name'];
            $tmp['user_avatar'] = $user['user_avatar'];
            $tmp['is_certification'] = $user['is_certification'];
            $return[] = $tmp;
        }
        return ['list'=>$return,'count'=>$count];
    }

    /**
     * @return AddressService
     */
    protected function getAddressBridge(){
        return new AddressBridge(new AddressService());
    }
}
