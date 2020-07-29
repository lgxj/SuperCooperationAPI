<?php


namespace App\Listeners\TaskOrder;


use App\Bridges\Trade\CompensateBridge;
use App\Bridges\Trade\DetailTaskOrderBridge;
use App\Consts\MessageConst;
use App\Consts\Trade\PayConst;
use App\Events\TaskOrder\TaskHelperCompensatePayEvent;
use App\Exceptions\BusinessException;
use App\Listeners\ScEventListener;
use App\Services\Trade\Fund\CompensateService;
use App\Services\Trade\Order\Employer\DetailTaskOrderService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class TaskHelperCompensatePayEventSubscriber extends ScEventListener
{
    /**
     * @param TaskHelperCompensatePayEvent $event
     * @throws BusinessException
     */
    public function handleSendMessage(TaskHelperCompensatePayEvent $event){
        $orderNo = $event->orderNo;
        $task = $this->getTaskDetailBridge()->getOrder($orderNo,'');
        if(empty($task)){
            Log::error("TaskHelperCancelEvent task order not exist order_no:{$orderNo}");
        }
        $compensateBridge = $this->getCompensateBridge();
        $cancelCompensate = $compensateBridge->getUserCompensateByToUserId($orderNo,$task['user_id'],PayConst::INOUT_HELPER_CANCEL_COMPENSATE_COMPLETE);
        if($cancelCompensate){
            single_order_send_message(0,MessageConst::TYPE_ORDER_HELPER_CANCEL_COMPENSATE,$orderNo,$cancelCompensate['compensate_price']);
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
            'App\Events\TaskOrder\TaskHelperCompensatePayEvent',
            'App\Listeners\TaskOrder\TaskHelperCompensatePayEventSubscriber@handleSendMessage'
        );
    }

    /**
     * @param TaskHelperCompensatePayEvent $event
     * @param \Exception $exception
     */
    public function failed(TaskHelperCompensatePayEvent $event, $exception)
    {
        Log::error("TaskHelperCompensatePayEvent failed message :{$exception->getMessage()} orderNO:{$event->orderNo}");
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
