<?php


namespace App\Listeners\TaskOrder;


use App\Bridges\Trade\EmployerBridge;
use App\Consts\MessageConst;
use App\Consts\Trade\OrderConst;
use App\Events\TaskOrder\TaskReceiveEvent;
use App\Exceptions\BusinessException;
use App\Listeners\ScEventListener;
use App\Services\Trade\Order\Employer\EmployerService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class TaskReceiveEventSubscriber extends ScEventListener
{
    /**
     * @param TaskReceiveEvent $event
     * @throws BusinessException
     */
    public function handleSendMessage(TaskReceiveEvent $event){
        $orderNo = $event->orderNo;
        $this->getTaskDayBridge()->increment('receive_num',1);
        if($event->orderType !=  OrderConst::TYPE_COMPETITION) {
            single_order_send_message(0, MessageConst::TYPE_ORDER_EMPLOYER_RECEIVE, $orderNo);
        }else{
            single_order_send_message(0, MessageConst::TYPE_ORDER_EMPLOYER_COMPETITION, $orderNo);
        }
    }

    /**
     * 竞价订单只有被确认后才从任务大厅删除
     *
     * @param TaskReceiveEvent $event
     */
    public function delEmployerYunTuAddress(TaskReceiveEvent $event){
        if($event->orderType !=  OrderConst::TYPE_COMPETITION) {
            $this->getTradeEmployerBridge()->deleteEmployerYuTuAddressByOrderNo($event->orderNo);
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
            'App\Events\TaskOrder\TaskReceiveEvent',
            'App\Listeners\TaskOrder\TaskReceiveEventSubscriber@delEmployerYunTuAddress'
        );

        $events->listen(
            'App\Events\TaskOrder\TaskReceiveEvent',
            'App\Listeners\TaskOrder\TaskReceiveEventSubscriber@handleSendMessage'
        );
    }

    /**
     * @param TaskReceiveEvent $event
     * @param \Exception $exception
     */
    public function failed(TaskReceiveEvent $event, $exception)
    {
        Log::error("TaskReceiveEvent failed message :{$exception->getMessage()} orderNO:{$event->orderNo}");
    }



    /**
     * @return EmployerService
     */
    protected function getTradeEmployerBridge(){
        return new EmployerBridge(new EmployerService());
    }
}
