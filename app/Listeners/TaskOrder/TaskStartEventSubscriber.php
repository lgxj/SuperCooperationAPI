<?php


namespace App\Listeners\TaskOrder;


use App\Bridges\Trade\EmployerBridge;
use App\Consts\MessageConst;
use App\Events\TaskOrder\TaskStartEvent;
use App\Exceptions\BusinessException;
use App\Listeners\ScEventListener;
use App\Services\Trade\Order\Employer\EmployerService;
use App\Services\Trade\Order\TaskNoticeService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class TaskStartEventSubscriber extends ScEventListener
{

    /**
     * 向云图注册
     * @param TaskStartEvent $event
     * @throws BusinessException
     */
    public function handleYunTuMap(TaskStartEvent $event){
        $detailTaskOrderBridge = $this->getTradeEmployerBridge();
        $orderNo = $event->orderNo;
        $detailTaskOrderBridge->saveEmployerYuTuAddress($orderNo);
    }

    /**
     * 推送附近的帮手
     *
     * @param TaskStartEvent $event
     * @throws BusinessException
     */
    public function handlePushNearByHelper(TaskStartEvent $event){
        $orderNo = $event->orderNo;
        $this->getTaskDayBridge()->increment('pay_num',1);
        if($event->notifyPrice > 0){
            $this->getTaskDayBridge()->increment('pay_total',$event->notifyPrice);
        }
        (new TaskNoticeService())->pushTaskToHelper($orderNo);
    }

    /**
     * 为订阅者注册监听器.
     *
     * @param  Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'App\Events\TaskOrder\TaskStartEvent',
            'App\Listeners\TaskOrder\TaskStartEventSubscriber@handleYunTuMap'
        );

        $events->listen(
            'App\Events\TaskOrder\TaskStartEvent',
            'App\Listeners\TaskOrder\TaskStartEventSubscriber@handlePushNearByHelper'
        );
    }

    /**
     * @param TaskStartEvent $event
     * @param \Exception $exception
     */
    public function failed(TaskStartEvent $event, $exception)
    {
        Log::error("TaskStartEvent failed message :{$exception->getMessage()} orderNO:{$event->orderNo}");
    }



    /**
     * @return EmployerService
     */
    protected function getTradeEmployerBridge(){
        return new EmployerBridge(new EmployerService());
    }
}
