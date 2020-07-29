<?php


namespace App\Listeners\TaskOrder;


use App\Bridges\Trade\EmployerBridge;
use App\Events\TaskOrder\TaskReverseStartEvent;
use App\Exceptions\BusinessException;
use App\Listeners\ScEventListener;
use App\Services\Trade\Order\Employer\EmployerService;
use App\Services\Trade\Order\TaskNoticeService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class TaskReverseStartEventSubscriber extends ScEventListener
{
    /**
     * 推送附近的帮手
     *
     * @param TaskReverseStartEvent $event
     * @throws BusinessException
     */
    public function handlePushNearByHelper(TaskReverseStartEvent $event){
        $orderNo = $event->orderNo;
        (new TaskNoticeService())->pushTaskToHelper($orderNo);
    }

    /**
     * @param TaskReverseStartEvent $event
     * @throws BusinessException
     */
    public function handleYunTuMap(TaskReverseStartEvent $event){
        $detailTaskOrderBridge = $this->getTradeEmployerBridge();
        $orderNo = $event->orderNo;
        $detailTaskOrderBridge->saveEmployerYuTuAddress($orderNo);
    }
    /**
     * 为订阅者注册监听器.
     *
     * @param  Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'App\Events\TaskOrder\TaskReverseStartEvent',
            'App\Listeners\TaskOrder\TaskReverseStartEventSubscriber@handleYunTuMap'
        );

        $events->listen(
            'App\Events\TaskOrder\TaskReverseStartEvent',
            'App\Listeners\TaskOrder\TaskReverseStartEventSubscriber@handlePushNearByHelper'
        );
    }

    /**
     * @param TaskReverseStartEvent $event
     * @param \Exception $exception
     */
    public function failed(TaskReverseStartEvent $event, $exception)
    {
        Log::error("TaskReverseStartEvent failed message :{$exception->getMessage()} orderNO:{$event->orderNo}");
    }



    /**
     * @return EmployerService
     */
    protected function getTradeEmployerBridge(){
        return new EmployerBridge(new EmployerService());
    }
}
