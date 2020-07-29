<?php


namespace App\Listeners\TaskOrder;


use App\Bridges\Trade\CompensateBridge;
use App\Bridges\Trade\DetailTaskOrderBridge;
use App\Bridges\Trade\EmployerBridge;
use App\Consts\MessageConst;
use App\Consts\Trade\PayConst;
use App\Events\TaskOrder\TaskEmployerCancelEvent;
use App\Exceptions\BusinessException;
use App\Listeners\ScEventListener;
use App\Services\Trade\Fund\CompensateService;
use App\Services\Trade\Order\Employer\DetailTaskOrderService;
use App\Services\Trade\Order\Employer\EmployerService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class TaskEmployerCancelSubscriber  extends ScEventListener
{
    /**
     * @param TaskEmployerCancelEvent $event
     * @throws BusinessException
     */
    public function handleSendMessage(TaskEmployerCancelEvent $event){
        $orderNo = $event->orderNo;
        $task = $this->getTaskDetailBridge()->getOrder($orderNo,'');
        if(empty($task)){
            Log::error("TaskEmployerCancelEvent task order not exist order_no:{$orderNo}");
        }
        $this->getTaskDayBridge()->increment('employer_cancel_num',1);
        if($task['helper_user_id'] > 0) {
            single_order_send_message(0, MessageConst::TYPE_ORDER_EMPLOYER_CANCEL, $orderNo);
            $compensateBridge = $this->getCompensateBridge();
            $cancelCompensate = $compensateBridge->getUserCompensateByToUserId($orderNo, $task['helper_user_id'], PayConst::INOUT_EMPLOYER_COMPENSATE_COMPLETE);
            if ($cancelCompensate) {
                single_order_send_message(0, MessageConst::TYPE_ORDER_EMPLOYER_CANCEL_COMPENSATE, $orderNo, $cancelCompensate['compensate_price']);
            }
        }
    }

    public function delEmployerYunTuAddress(TaskEmployerCancelEvent $event){
        $this->getTradeEmployerBridge()->deleteEmployerYuTuAddressByOrderNo($event->orderNo);
    }

    /**
     * 为订阅者注册监听器.
     *
     * @param  Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'App\Events\TaskOrder\TaskEmployerCancelEvent',
            'App\Listeners\TaskOrder\TaskEmployerCancelSubscriber@delEmployerYunTuAddress'
        );

        $events->listen(
            'App\Events\TaskOrder\TaskEmployerCancelEvent',
            'App\Listeners\TaskOrder\TaskEmployerCancelSubscriber@handleSendMessage'
        );
    }

    /**
     * @param TaskEmployerCancelEvent $event
     * @param \Exception $exception
     */
    public function failed(TaskEmployerCancelEvent $event, $exception)
    {
        Log::error("TaskEmployerCancelEvent failed message :{$exception->getMessage()} orderNO:{$event->orderNo}");
    }



    /**
     * @return EmployerService
     */
    protected function getTradeEmployerBridge(){
        return new EmployerBridge(new EmployerService());
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
