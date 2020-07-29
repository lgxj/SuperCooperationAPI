<?php


namespace App\Listeners\TaskOrder;


use App\Bridges\Trade\CompensateBridge;
use App\Bridges\Trade\DetailTaskOrderBridge;
use App\Consts\MessageConst;
use App\Consts\Trade\PayConst;
use App\Events\TaskOrder\TaskDeliveryEvent;
use App\Exceptions\BusinessException;
use App\Listeners\ScEventListener;
use App\Services\Trade\Fund\CompensateService;
use App\Services\Trade\Order\Employer\DetailTaskOrderService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class TaskDeliveryEventSubscriber extends ScEventListener
{
    /**
     * @param TaskDeliveryEvent $event
     * @throws BusinessException
     */
    public function handleSendMessage(TaskDeliveryEvent $event){
        $orderNo = $event->orderNo;
        $task = $this->getTaskDetailBridge()->getOrder($orderNo,'');
        if(empty($task)){
            Log::error("TaskDeliveryEvent task order not exist order_no:{$orderNo}");
        }
        $this->getTaskDayBridge()->increment('delivery_num',1);
        $compensateBridge = $this->getCompensateBridge();
        $cancelCompensate = $compensateBridge->getUserCompensateByToUserId($orderNo,$task['user_id'],PayConst::INOUT_OVERTIME_COMPENSATE);
        if($cancelCompensate){
            single_order_send_message(0,MessageConst::TYPE_ORDER_HELPER_OVERTIME_COMPENSATE,$orderNo,$cancelCompensate['compensate_price']);
        }else{
            single_order_send_message(0,MessageConst::TYPE_ORDER_EMPLOYER_DELIVERY,$orderNo);
        }
    }

    /**
     * 为订阅者注册监听器.
     *
     * @param  Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'App\Events\TaskOrder\TaskDeliveryEvent',
            'App\Listeners\TaskOrder\TaskDeliveryEventSubscriber@handleSendMessage'
        );
    }

    /**
     * @param TaskDeliveryEvent $event
     * @param \Exception $exception
     */
    public function failed(TaskDeliveryEvent $event, $exception)
    {
        Log::error("TaskConfirmReceiveEvent failed message :{$exception->getMessage()} orderNO:{$event->orderNo}");
    }

    /**
     * @return CompensateService
     */
    public function getCompensateBridge(){
        return new CompensateBridge(new CompensateService());
    }

    /**
     * @return DetailTaskOrderService
     */
    public function getTaskDetailBridge(){
        return new DetailTaskOrderBridge(new DetailTaskOrderService());
    }
}
