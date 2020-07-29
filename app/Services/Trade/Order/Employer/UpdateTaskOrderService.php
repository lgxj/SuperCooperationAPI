<?php


namespace App\Services\Trade\Order\Employer;


use App\Consts\DBConnection;
use App\Consts\ErrorCode\TaskOrderErrorCode;
use App\Consts\Trade\OrderConst;
use App\Events\TaskOrder\TaskUpdateEvent;
use App\Exceptions\BusinessException;
use App\Models\Trade\Entity\TaskOrderEntity;
use App\Models\Trade\Order\TaskOrder;
use App\Models\Trade\Order\Text;
use App\Models\Trade\Order\TypeChange;
use App\Services\Trade\Order\BaseTaskOrderService;
use App\Services\Trade\Traits\OrderEntityCheckTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * 雇主更新任务
 *
 * Class UpdateTaskOrderService
 * @package App\Services\Trade\Order\Employer
 */
class UpdateTaskOrderService extends BaseTaskOrderService
{
    use OrderEntityCheckTrait;


    /**
     * 不支持减价，只能向上增，涉及到退款延迟到任务取消/完成时处理
     *
     * @param TaskOrderEntity $taskOrderEntity
     * @return array
     * @throws BusinessException
     */
    public function update(TaskOrderEntity $taskOrderEntity)
    {
        $orderNo = $taskOrderEntity->orderNo;
        if($taskOrderEntity->orderNo <= 0){
            throw new BusinessException("订单号错误",TaskOrderErrorCode::SAVE_PARAM_CHECK_NO);
        }

        $taskOrder = TaskOrder::where('order_no',$orderNo)->first();
        if(empty($taskOrder)){
            throw new BusinessException("任务单不存在",TaskOrderErrorCode::SAVE_PARAM_CHECK_TASK_NOT_EXIST);
        }

        if(in_array($taskOrder->order_state,OrderConst::employerUnModifyStateList())){
            throw new BusinessException("任务正在进行中或者已完成，不能修改",TaskOrderErrorCode::SAVE_PARAM_CHECK_STATE_UN_MODIFY);
        }
        if($taskOrderEntity->changePrice < 0){
            throw new BusinessException("更新的服务费错误",TaskOrderErrorCode::SAVE_PARAM_CHECK_SERVICE_NO_CHANGE_URGE);
        }

        $this->taskOrderPriceToDb($taskOrderEntity);
        $this->checkGeneral($taskOrderEntity);
        $this->checkOrderText($taskOrderEntity);
        $this->checkTime($taskOrderEntity);
        $this->checkPrice($taskOrderEntity);
        $this->checkOriginPrice($taskOrder,$taskOrderEntity);
        $this->checkOrderServiceChange($taskOrder,$taskOrderEntity);
        $this->checkOrderType($taskOrder,$taskOrderEntity);
        $connection = DBConnection::getTradeConnection();
        try {
            $connection->beginTransaction();
            $this->updateText($taskOrderEntity);
            $this->addOrderAddressList($taskOrderEntity);
            $taskOrder->order_name = $taskOrderEntity->orderName;
            $taskOrder->category = $taskOrderEntity->category;
            $taskOrder->helper_level = $taskOrderEntity->helperLevel;
            $taskOrder->start_time = $taskOrderEntity->startTime;
            $taskOrder->end_time = $taskOrderEntity->endTime;
            if($taskOrder['order_state'] == OrderConst::EMPLOYER_STATE_UN_START){
                $taskOrder->origin_price = $taskOrderEntity->originPrice;
                $taskOrder->pay_price =  $taskOrderEntity->payPrice;
                $latestPriceChange = $this->updatePriceTaskUnStart($taskOrder,$taskOrderEntity);
            }else{
                $latestPriceChange = $this->updatePriceTaskStart($taskOrder,$taskOrderEntity);//支付成功后再更新价格
            }
            $this->addOrderTypeChange($taskOrder,$taskOrderEntity);
            $taskOrder->save();
            if(in_array($taskOrder['order_state'],[OrderConst::EMPLOYER_STATE_UN_RECEIVE,OrderConst::EMPLOYER_STATE_UN_CONFIRM])){
                $this->getOrderSearchService()->saveOrderSearch($orderNo);
            }
            $connection->commit();
            $taskOrderArray = $taskOrder->toArray();
            $taskOrderArray['price_list'] = $latestPriceChange;
            event(new TaskUpdateEvent($orderNo,$taskOrder['order_state']));
            return $taskOrderArray;
        }catch (\Exception $e) {
            $connection->rollBack();
            \Log::error("任务修改失败 message:{$e->getMessage()}");
            throw new BusinessException($e->getMessage(),TaskOrderErrorCode::SAVE_FAILED);
        }

    }

    /**
     * @param Model $taskModel
     * @param TaskOrderEntity $taskOrderEntity
     * @throws BusinessException
     * @return  array
     */
    protected function updatePriceTaskUnStart(Model $taskModel,TaskOrderEntity $taskOrderEntity){
        $detailOrderService = $this->getDetailService();
        $taskOrderNo = $taskOrderEntity->orderNo;
        $priceChange = $detailOrderService->getOrderPriceChangeFirstPay($taskOrderNo);
        if($priceChange){
            throw new BusinessException("您已支付，不用重新支付");
        }
        if($taskModel->order_state > OrderConst::EMPLOYER_STATE_UN_START){
            throw new BusinessException("您已支付，不用重新支付!");
        }
        $detailOrderService->deleteUnPayService($taskOrderNo);
        $this->batchAddOrderService($taskOrderEntity,OrderConst::PRICE_OP_STATE_UN_HANDLE,OrderConst::PRICE_CHANGE_ORDER_PAY);
        return $detailOrderService->getLatestChangePayPrice($taskOrderEntity->orderNo,OrderConst::PRICE_CHANGE_ORDER_PAY,true);
    }

    /**
     * @param Model $taskModel
     * @param TaskOrderEntity $orderEntity
     * @return array
     * @throws BusinessException
     */
    protected function updatePriceTaskStart(Model $taskModel,TaskOrderEntity $orderEntity){
        $taskOrderNo = $orderEntity->orderNo;
        $detailOrderService = $this->getDetailService();
        $priceChange = $detailOrderService->getOrderPriceChangeFirstPay($taskOrderNo);
        if(empty($priceChange)){
            throw new BusinessException("您首次支付还示完成，不能修改任务，稍后再试");
        }
        if($taskModel->order_state == OrderConst::EMPLOYER_STATE_UN_START){
            throw new BusinessException("您首次支付还示完成，不能修改任务，稍后再试!");
        }
        $enableServices = $detailOrderService->getEnableServiceByOrderNo($taskOrderNo);
        $urgeServicePrice  = $enableServices[OrderConst::SERVICE_PRICE_TYPE_URGE] ?? 0;
        $faceServicePrice  = $enableServices[OrderConst::SERVICE_PRICE_TYPE_FACE] ?? 0;
        $insuranceServicePrice  = $enableServices[OrderConst::SERVICE_PRICE_TYPE_INSURANCE] ?? 0;

        if($urgeServicePrice && $urgeServicePrice != $orderEntity->urgentPrice){
            throw new BusinessException("任务单加急已生效，不能修改");
        }

        if($insuranceServicePrice && $insuranceServicePrice != $orderEntity->insurancePrice){
            throw new BusinessException("任务单保险已生效，不能修改");
        }

        if($faceServicePrice && $faceServicePrice !=  $orderEntity->facePrice){
            throw new BusinessException("任务单人脸识别已开启，不能修改");
        }
        $serviceChange = ((!$urgeServicePrice && $orderEntity->urgentPrice > 0) || (!$insuranceServicePrice && $orderEntity->insurancePrice > 0)  || (!$faceServicePrice && $orderEntity->facePrice > 0) );
        $detailOrderService->deleteUnPayService($taskOrderNo);
        if($serviceChange || $orderEntity->changePrice > 0) {
            $this->batchAddOrderService($orderEntity, OrderConst::PRICE_OP_STATE_UN_HANDLE, OrderConst::PRICE_CHANGE_MAKE_UP);
        }
        return $detailOrderService->getLatestChangePayPrice($orderEntity->orderNo,OrderConst::PRICE_CHANGE_MAKE_UP,true);
    }

    protected function updateText(TaskOrderEntity $taskOrderEntity)
    {
        $orderText = Text::where('order_no',$taskOrderEntity->orderNo)->first();
        if(empty($orderText)){
            return [];
        }
        $orderText->voice_text = $taskOrderEntity->voiceText;
        $orderText->voice_url = $taskOrderEntity->voiceUrl;
        $orderText->memo = $taskOrderEntity->memo;
        if($taskOrderEntity->attachmentList){
            $orderText->attachment_url_list = json_encode($taskOrderEntity->attachmentList);
        }
        $orderText->save();
        return $orderText->toArray();
    }


    /**
     * @param Model $taskOrder
     * @param TaskOrderEntity $taskOrderEntity
     * @throws BusinessException
     */
    protected function addOrderTypeChange(Model $taskOrder ,TaskOrderEntity $taskOrderEntity){
        $this->checkOrderType($taskOrder,$taskOrderEntity);
        if($taskOrder['order_type'] != $taskOrderEntity->orderType) {
            $taskOrder->order_type = $taskOrderEntity->orderType;
            $typeChange = new TypeChange();
            $typeChange->user_id = $taskOrderEntity->userId;
            $typeChange->order_no = $taskOrderEntity->orderNo;
            $typeChange->before_order_type = $taskOrder->order_type;
            $typeChange->order_type = $taskOrderEntity->orderType;
            $typeChange->save();
            if(in_array($taskOrder['order_state'] ,OrderConst::helperCanReceiveList())){
                if($taskOrder['order_type'] == OrderConst::TYPE_COMPETITION){
                    $taskOrder->order_state = OrderConst::EMPLOYER_STATE_UN_RECEIVE;
                }elseif($taskOrder['order_type'] == OrderConst::TYPE_GENERAL){
                    $taskOrder->order_state = OrderConst::EMPLOYER_STATE_UN_CONFIRM;
                }
            }
        }
    }

}
