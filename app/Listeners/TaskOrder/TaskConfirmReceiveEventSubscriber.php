<?php


namespace App\Listeners\TaskOrder;


use App\Bridges\Trade\EmployerBridge;
use App\Consts\MessageConst;
use App\Events\TaskOrder\TaskConfirmReceiveEvent;
use App\Exceptions\BusinessException;
use App\Listeners\ScEventListener;
use App\Services\Trade\Order\Employer\EmployerService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class TaskConfirmReceiveEventSubscriber extends ScEventListener
{
    /**
     * @param TaskConfirmReceiveEvent $event
     * @throws BusinessException
     */
    public function handleSendMessage(TaskConfirmReceiveEvent $event){
        $orderNo = $event->orderNo;
        $this->getTaskDayBridge()->increment('confirm_receive_num',1);
        if($event->notifyPrice > 0){
            $this->getTaskDayBridge()->increment('pay_total',$event->notifyPrice);
        }
        single_order_send_message(0,MessageConst::TYPE_ORDER_HELPER_COMPETITION,$orderNo);
    }

    public function delEmployerYunTuAddress(TaskConfirmReceiveEvent $event){
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
            'App\Events\TaskOrder\TaskConfirmReceiveEvent',
            'App\Listeners\TaskOrder\TaskConfirmReceiveEventSubscriber@delEmployerYunTuAddress'
        );

        $events->listen(
            'App\Events\TaskOrder\TaskConfirmReceiveEvent',
            'App\Listeners\TaskOrder\TaskConfirmReceiveEventSubscriber@handleSendMessage'
        );
    }

    /**
     * @param TaskConfirmReceiveEvent $event
     * @param \Exception $exception
     */
    public function failed(TaskConfirmReceiveEvent $event, $exception)
    {
        Log::error("TaskConfirmReceiveEvent failed message :{$exception->getMessage()} orderNO:{$event->orderNo}");
    }



    /**
     * @return EmployerService
     */
    protected function getTradeEmployerBridge(){
        return new EmployerBridge(new EmployerService());
    }
}
