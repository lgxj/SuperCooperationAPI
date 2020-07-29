<?php


namespace App\Listeners\TaskOrder;


use App\Bridges\Trade\DetailTaskOrderBridge;
use App\Consts\MessageConst;
use App\Events\TaskOrder\TaskRefuseDeliveryEvent;
use App\Exceptions\BusinessException;
use App\Listeners\ScEventListener;
use App\Services\Trade\Order\Employer\DetailTaskOrderService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class TaskRefuseDeliverySubscriber extends ScEventListener
{
    /**
     * @param TaskRefuseDeliveryEvent $event
     * @throws BusinessException
     */
    public function handleSendMessage(TaskRefuseDeliveryEvent $event){
        $orderNo = $event->orderNo;
        $task = $this->getTaskDetailBridge()->getOrder($orderNo,'');
        if(empty($task)){
            Log::error("TaskRefuseDeliveryEvent task order not exist order_no:{$orderNo}");
        }
        $this->getTaskDayBridge()->increment('refuse_delivery_num',1);
        single_order_send_message(0,MessageConst::TYPE_ORDER_EMPLOYER_REFUSE_DELIVERY,$orderNo);
    }
    /**
     * 为订阅者注册监听器.
     *
     * @param  Dispatcher  $events
     */
    public function subscribe($events)
    {

        $events->listen(
            'App\Events\TaskOrder\TaskRefuseDeliveryEvent',
            'App\Listeners\TaskOrder\TaskRefuseDeliverySubscriber@handleSendMessage'
        );
    }

    /**
     * @param TaskRefuseDeliveryEvent $event
     * @param \Exception $exception
     */
    public function failed(TaskRefuseDeliveryEvent $event, $exception)
    {
        Log::error("TaskRefuseDeliveryEvent failed message :{$exception->getMessage()} orderNO:{$event->orderNo}");
    }

    /**
     * @return DetailTaskOrderService
     */
    public function getTaskDetailBridge(){
        return new DetailTaskOrderBridge(new DetailTaskOrderService());
    }
}
