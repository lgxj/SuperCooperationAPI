<?php


namespace App\Listeners\TaskOrder;


use App\Bridges\Trade\CompensateBridge;
use App\Bridges\Trade\DetailTaskOrderBridge;
use App\Consts\MessageConst;
use App\Consts\Trade\PayConst;
use App\Events\TaskOrder\TaskCompleteEvent;
use App\Exceptions\BusinessException;
use App\Listeners\ScEventListener;
use App\Services\Trade\Fund\CompensateService;
use App\Services\Trade\Order\Employer\DetailTaskOrderService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class TaskCompleteEventSubscriber extends ScEventListener
{

    /**
     * @param TaskCompleteEvent $event
     * @throws BusinessException
     */
    public function handleSendMessage(TaskCompleteEvent $event){
        $orderNo = $event->orderNo;
        $task = $this->getTaskDetailBridge()->getOrder($orderNo,'');
        if(empty($task)){
            Log::error("TaskCompleteEvent task order not exist order_no:{$orderNo}");
        }
        $this->getTaskDayBridge()->increment('complete_num',1);
        single_order_send_message(0,MessageConst::TYPE_ORDER_HELPER_COMPLETE,$orderNo);
        $compensateBridge = $this->getCompensateBridge();
        $overtimeCompensate = $compensateBridge->getUserCompensateByToUserId($orderNo,$task['user_id'],PayConst::INOUT_OVERTIME_COMPENSATE);
        if($overtimeCompensate){
            single_order_send_message(0,MessageConst::TYPE_ORDER_EMPLOYER_OVERTIME_COMPENSATE,$orderNo,$overtimeCompensate['compensate_price']);
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
            'App\Events\TaskOrder\TaskCompleteEvent',
            'App\Listeners\TaskOrder\TaskCompleteEventSubscriber@handleSendMessage'
        );
    }

    /**
     * @param TaskCompleteEvent $event
     * @param \Exception $exception
     */
    public function failed(TaskCompleteEvent $event, $exception)
    {
        Log::error("TaskCompleteEvent failed message :{$exception->getMessage()} orderNO:{$event->orderNo}");
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
