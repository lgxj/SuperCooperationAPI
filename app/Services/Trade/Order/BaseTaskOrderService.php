<?php


namespace App\Services\Trade\Order;

use App\Consts\Trade\OrderConst;
use App\Consts\Trade\PayConst;
use App\Consts\UserConst;
use App\Exceptions\BusinessException;
use App\Models\Trade\Entity\TaskOrderEntity;
use App\Models\Trade\Order\Address;
use App\Models\Trade\Order\Cancel;
use App\Models\Trade\Order\Category;
use App\Models\Trade\Order\PriceChange;
use App\Models\Trade\Order\ReceiverOrder;
use App\Models\Trade\Order\Service;
use App\Models\Trade\Order\StateChange;
use App\Services\Trade\Order\Employer\DetailTaskOrderService;
use App\Services\Trade\Traits\ModelTrait;
use App\Services\Trade\Traits\ServiceTrait;
use App\Utils\UnderLineToHump;
use App\Utils\UniqueNo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class BaseTaskOrderService
{
    use ServiceTrait;

    use ModelTrait;

    public function getCategoryKV(){
        return Category::orderByDesc('sort')->get()->pluck('category_name','category_id')->toArray();
    }

    public function getCategoryList(){
        return Category::orderByDesc('sort')->get()->toArray();
    }

    public function updateEmployerOrderState(Model $taskOrder,$state,$cancelType = null){
        if(empty($taskOrder)){
            return [];
        }
        $dateTime = Carbon::now();
        if(in_array($state,[OrderConst::EMPLOYER_STATE_UN_CONFIRM,OrderConst::EMPLOYER_STATE_UN_RECEIVE])){
            $taskOrder->order_state = $state;
            $taskOrder->pay_state = PayConst::STATE_PAY;
            $taskOrder->pay_time = $dateTime;
        }
        if($state == OrderConst::EMPLOYER_STATE_RECEIVE){
            $taskOrder->order_state = $state;
            $taskOrder->receive_time = $dateTime;
        }
        if($state == OrderConst::EMPLOYER_STATE_COMPLETE){
            $taskOrder->order_state = $state;
            $taskOrder->success_time = $dateTime;
        }
        if($state == OrderConst::EMPLOYER_STATE_DELIVERED){
            $taskOrder->order_state = $state;
            $taskOrder->deliver_time = $dateTime;
        }
        if($state == OrderConst::EMPLOYER_STATE_CANCEL){
            $taskOrder->order_state = $state;
            $taskOrder->cancel_time = $dateTime;
            if(!is_null($cancelType)){
                $taskOrder->cancel_type = $cancelType;
            }
        }
        if($state == OrderConst::EMPLOYER_STATE_REFUSE_DELIVERY){
            $taskOrder->order_state = $state;
        }
        $taskOrder->save();
        return $taskOrder;
    }

    public function updateReceiverOrderState(Model $receiver,$state,$cancelType = null){
        if(empty($receiver)){
            return [];
        }
        $dateTime = Carbon::now();
        $receiver->receive_state = $state;
        if($state == OrderConst::HELPER_STATE_CANCEL){
            $receiver->cancel_time = $dateTime;
            if(!is_null($cancelType)){
                $receiver->cancel_type = $cancelType;
            }
        }
        if($receiver['order_type'] == OrderConst::TYPE_COMPETITION && $state == OrderConst::HELPER_STATE_RECEIVE){
            $receiver->confirm_time = $dateTime;
        }
        $receiver->save();
        return $receiver;
    }

    public function addOrderStateChange($userId,$orderNo,$beforeState,$state,$userType = UserConst::TYPE_EMPLOYER){
        if($beforeState == $state){
            return [];
        }
        $stateChange = new  StateChange();
        $stateChange->order_no = $orderNo;
        $stateChange->user_id = $userId;
        $stateChange->before_state = $beforeState;
        $stateChange->state = $state;
        $stateChange->user_type = $userType;
        $stateChange->current_user_id = getLoginUserId();
        $stateChange->save();
        return $stateChange->toArray();
    }

    public function orderEntityToArray(TaskOrderEntity $taskOrderEntity) : array
    {
        $data = get_object_vars($taskOrderEntity);
        $newData = [];
        foreach ($data as $key=>$value){
            $newKey = UnderLineToHump::humpToUnderLine($key);
            $newData[$newKey] = $value;
        }
        return $newData;
    }


    public function convertToOrderEntity(array $array) : TaskOrderEntity
    {
        $taskOrderEntity = new TaskOrderEntity();
        foreach ($array as $key=>$value)
        {
            $entityKey = UnderLineToHump::underLineToHump($key);
            $taskOrderEntity->{$entityKey} = $value;
        }
        return $taskOrderEntity;
    }


    /**
     *
     * @param TaskOrderEntity $taskOrderEntity
     * @param int $opState
     * @param int $mainOrderPriceChangeType 价格变更 进行首次支付/加价支付/确认报价/帮手补差价。 服务价格绑定在主模式上
     * @param int $inout
     * @return string
     * @throws BusinessException
     */
    protected function batchAddOrderService(TaskOrderEntity $taskOrderEntity,$opState=OrderConst::PRICE_OP_STATE_UN_HANDLE,$mainOrderPriceChangeType = OrderConst::PRICE_CHANGE_ORDER_PAY,$inout = OrderConst::INOUT_OUT){
        $orderService = $this->getServiceModel();
        $orderPriceChange = $this->getPriceChangeModel();
        $detailOrderService = $this->getDetailService();
        $orderNo = $taskOrderEntity->orderNo;
        $latestPriceChange = $detailOrderService->getLatestOrderPriceChangeWithNumberKey($orderNo,$mainOrderPriceChangeType);
        $services = $detailOrderService->geServicesByOrderNo($orderNo);
        $enableServices = $detailOrderService->getEnableServiceByOrderNo($orderNo);
        $data = [];
        $priceChange = [];
        $userId = $taskOrderEntity->userId;
        $waterNo = UniqueNo::buildPriceWaterNo($taskOrderEntity->userId,$mainOrderPriceChangeType);


        $insurancePrice = $taskOrderEntity->insurancePrice;
        $urgePrice = $taskOrderEntity->urgentPrice;
        $facePrice = $taskOrderEntity->facePrice;
        $isSameChange = true;

        if($taskOrderEntity->insurancePrice > 0){
            if(!isset($services[OrderConst::SERVICE_PRICE_TYPE_INSURANCE])) {
                $data[] = ['user_id' => $userId, 'service_price' => $insurancePrice, 'service_type' => OrderConst::SERVICE_PRICE_TYPE_INSURANCE, 'order_no' => $orderNo, 'pay_state' => $opState];
            }
            if(!isset($enableServices[OrderConst::SERVICE_PRICE_TYPE_INSURANCE])) {
                $priceChange[] = ['user_id' => $userId, 'order_no' => $orderNo, 'water_no' => $waterNo, 'before_price' => 0, 'price' => $insurancePrice, 'inout' => $inout, 'change_type' => OrderConst::SERVICE_PRICE_TYPE_INSURANCE];
            }
        }
        if($taskOrderEntity->urgentPrice > 0){
            if(!isset($services[OrderConst::SERVICE_PRICE_TYPE_URGE])) {
                $data[] = ['user_id' => $userId, 'service_price' => $urgePrice, 'service_type' => OrderConst::SERVICE_PRICE_TYPE_URGE, 'order_no' => $orderNo, 'pay_state' => $opState];
            }
            if(!isset($enableServices[OrderConst::SERVICE_PRICE_TYPE_URGE])) {
                $priceChange[] = ['user_id' => $userId, 'order_no' => $orderNo, 'water_no' => $waterNo, 'before_price' => 0, 'price' => $urgePrice, 'inout' => $inout, 'change_type' => OrderConst::SERVICE_PRICE_TYPE_URGE];
            }
        }
        if($taskOrderEntity->facePrice > 0){
            if(!isset($services[OrderConst::SERVICE_PRICE_TYPE_FACE])) {
                $data[] = ['user_id' => $userId, 'service_price' => $facePrice, 'service_type' => OrderConst::SERVICE_PRICE_TYPE_FACE, 'order_no' => $orderNo, 'pay_state' => $opState];
            }
            if(!isset($enableServices[OrderConst::SERVICE_PRICE_TYPE_FACE])) {
                $priceChange[] = ['user_id' => $userId, 'order_no' => $orderNo, 'water_no' => $waterNo, 'before_price' => 0, 'price' => $facePrice, 'inout' => $inout, 'change_type' => OrderConst::SERVICE_PRICE_TYPE_FACE];
            }
        }


        $mainOrderPriceChangeTypeList = OrderConst::getChangePriceIdentifyList();
        if(in_array($mainOrderPriceChangeType,array_keys($mainOrderPriceChangeTypeList))){
            //pay_price无论是否有值都会进一次插入操作
            $payPrice = $taskOrderEntity->payPrice;
            if(OrderConst::PRICE_CHANGE_ORDER_PAY == $mainOrderPriceChangeType) {
                $changePrice = $payPrice;
                $priceChange[] =  ['user_id'=>$userId,'order_no'=>$orderNo,'water_no'=>$waterNo,'before_price'=>0,'price'=>$changePrice,'inout'=>$inout,'change_type'=>$mainOrderPriceChangeType];
            }else {
                $changePrice = $taskOrderEntity->changePrice;
                $priceChange[] =  ['user_id'=>$userId,'order_no'=>$orderNo,'water_no'=>$waterNo,'before_price'=>$payPrice,'price'=>$changePrice,'inout'=>$inout,'change_type'=>$mainOrderPriceChangeType];
            }

        }
        collect($data)->each(function ($item) use($services,$orderService,$mainOrderPriceChangeType){
            if(!isset($services[$item['service_type']]) && $mainOrderPriceChangeType == OrderConst::PRICE_CHANGE_ORDER_PAY){
                $orderService->insert($item);//首次支付前可以插入未付款的服务，其它情况付款成功后再插入
            }
        });
        collect($priceChange)->each(function ($item) use($latestPriceChange,&$isSameChange){
            if(!isset($latestPriceChange[$item['change_type']])){
                $isSameChange = false;
                return;
            }
            $latestChangeTypePrice = $latestPriceChange[$item['change_type']];
            if($item['price'] != $latestChangeTypePrice){
                $isSameChange = false;
                return;
            }
        });
        //最后一次更新的价格相等不插入，防止大量重复插入
        if($priceChange && (!$isSameChange || count($latestPriceChange) != count($priceChange))) {
            $orderPriceChange->insert($priceChange);
        }
        return $waterNo;
    }


    public function taskOrderPriceToDb(TaskOrderEntity $taskOrderEntity){
        $taskOrderEntity->urgentPrice =  db_price($taskOrderEntity->urgentPrice);
        $taskOrderEntity->insurancePrice =  db_price($taskOrderEntity->insurancePrice);
        $taskOrderEntity->facePrice =  db_price($taskOrderEntity->facePrice);
        $taskOrderEntity->changePrice = db_price($taskOrderEntity->changePrice);
        $taskOrderEntity->originPrice = db_price($taskOrderEntity->originPrice);
        $taskOrderEntity->payPrice = $taskOrderEntity->originPrice;
    }


    public function addOrderAddressList(TaskOrderEntity $taskOrderEntity){
        $addressList = $taskOrderEntity->addressList;
        if(empty($addressList) || $taskOrderEntity->userId <= 0){
            return false;
        }
        $this->deleteOrderAddressByOrderNo($taskOrderEntity->orderNo);
        collect($addressList)->each(function ($address)use($taskOrderEntity){

            $address['user_id'] = $taskOrderEntity->userId;
            $validate = Validator::make($address,[
                'address_id'=>'required|integer',
                'user_name'=>'required',
                'user_phone'=>'required',
                'province'=>'required',
                'city'=>'required',
                'region'=>'required',
                'address_detail'=>'required'
            ],[
                'user_id.required' => '用户标识不能为空',
                'address_id.required' => '地区标识不能为空',
                'user_name.required' => '姓名不能为空',
                'user_phone.required'=>"电话不能为空",
                'province.required'=>"省份不能为空",
                'city.required'=>'城市不能为空',
                'region.required' => "城市或区域不能为空",
                'address_detail.required' => "详细地址不能为空"
            ]);
            if($validate->fails()){
                throw new BusinessException($validate->errors()->first());
            }
            $addressModel = $this->getAddressModel();
            $fields = $addressModel->getTableColumns();
            foreach ($fields as $field) {
                if ($field == $addressModel->getKeyName()) {
                    continue;
                }
                if (isset($address[$field])) {
                    $addressModel->$field = $address[$field];
                }
            }
            $addressModel->user_id = $taskOrderEntity->userId;
            $addressModel->order_no = $taskOrderEntity->orderNo;
            $addressModel->save();
        });
        return true;
    }

    public function addOrderCancel($orderNo,$userId,$userType,$cancelType,$reason = '',array $attachment = []){
        $cancel = [
            'order_no' => $orderNo,
            'user_id' => $userId,
            'user_type'=>$userType,
            'cancel_type' => $cancelType,
            'cancel_reason'=>$reason,
            'attachment_list' => json_encode($attachment)
        ];
        $validate = Validator::make($cancel,[
            'user_id'=>'required|integer',
            'order_no'=>'required',
            'user_type'=>'required|integer',
            'cancel_type'=>'required|integer'
        ],[
            'user_id.required' => '用户标识不能为空',
            'order_no.required' => '任务标识不能为空',
            'user_type.required' => '用户类型不能为空',
            'cancel_type.required'=>"取消类型不能为空"
        ]);
        if($validate->fails()){
            throw new BusinessException($validate->errors()->first());
        }

        $cancelModel =  $this->getCancelModel();
        $fields = $cancelModel->getTableColumns();
        foreach ($fields as $field) {
            if ($field == $cancelModel->getKeyName()) {
                continue;
            }
            if (isset($cancel[$field])) {
                $cancelModel->$field = $cancel[$field];
            }
        }
        $cancelModel->save();
        return $cancelModel->toArray();
    }

    public function deleteOrderAddressByOrderNo($orderNo){
        return Address::where('order_no',$orderNo)->delete();
    }

    public function deleteOrderCancelOrderNo($orderNo,$userId){
        if(empty($orderNo) || $userId <= 0){
            return 0;
        }
        return Cancel::where(['order_no'=>$orderNo,'user_id'=>$userId])->delete();
    }


    public function getValidReceiverByOrderNo($orderNo,$receiverUid){
        return ReceiverOrder::where(['order_no'=>$orderNo,'user_id'=>$receiverUid])->first();
    }


}
