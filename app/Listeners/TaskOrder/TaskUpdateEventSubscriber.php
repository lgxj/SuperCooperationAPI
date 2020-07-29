<?php


namespace App\Listeners\TaskOrder;


use App\Bridges\Trade\EmployerBridge;
use App\Consts\Trade\OrderConst;
use App\Events\TaskOrder\TaskUpdateEvent;
use App\Listeners\ScEventListener;
use App\Services\Trade\Order\Employer\EmployerService;
use Illuminate\Support\Facades\Log;

class TaskUpdateEventSubscriber extends ScEventListener
{
    /**
     * @param TaskUpdateEvent $event
     * @throws \App\Exceptions\BusinessException
     */
    public function handleYunTuMap(TaskUpdateEvent $event){
        $detailTaskOrderBridge = $this->getTradeEmployerBridge();
        $orderNo = $event->orderNo;
        if(in_array($event->taskState,[OrderConst::EMPLOYER_STATE_UN_CONFIRM,OrderConst::EMPLOYER_STATE_UN_RECEIVE])){
            $detailTaskOrderBridge->saveEmployerYuTuAddress($orderNo);
        }
    }
    /**
     * 为订阅者注册监听器.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'App\Events\TaskOrder\TaskUpdateEvent',
            'App\Listeners\TaskOrder\TaskUpdateEventSubscriber@handleYunTuMap'
        );
    }

    /**
     * @param TaskUpdateEvent $event
     * @param \Exception $exception
     */
    public function failed(TaskUpdateEvent $event, $exception)
    {
        Log::error("TaskUpdateEvent failed message :{$exception->getMessage()} orderNO:{$event->orderNo}");
    }

    /**
     * @return EmployerService
     */
    protected function getTradeEmployerBridge(){
        return new EmployerBridge(new EmployerService());
    }
}
