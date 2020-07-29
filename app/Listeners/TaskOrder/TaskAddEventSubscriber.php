<?php


namespace App\Listeners\TaskOrder;


use App\Bridges\Trade\EmployerBridge;
use App\Consts\Trade\OrderConst;
use App\Events\TaskOrder\TaskAddEvent;
use App\Listeners\ScEventListener;
use App\Services\Trade\Order\Employer\EmployerService;
use Illuminate\Support\Facades\Log;

class TaskAddEventSubscriber extends ScEventListener
{
    /**
     * 只是计算经纬度，不用存到云图
     *
     * @param TaskAddEvent $event
     * @throws \App\Exceptions\BusinessException
     */
    public function handleAddress(TaskAddEvent $event){
        $detailTaskOrderBridge = $this->getTradeEmployerBridge();
        $orderNo = $event->orderNo;
        $detailTaskOrderBridge->saveEmployerYuTuAddress($orderNo,false);
    }

    public function handleStatistics(TaskAddEvent $event){
        $this->getTaskDayBridge()->increment('publish_num',1);
        if($event->orderType == OrderConst::TYPE_COMPETITION){
            $this->getTaskDayBridge()->increment('pubish_competition_num',1);
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
            'App\Events\TaskOrder\TaskAddEvent',
            'App\Listeners\TaskOrder\TaskAddEventSubscriber@handleAddress'
        );

        $events->listen(
            'App\Events\TaskOrder\TaskAddEvent',
            'App\Listeners\TaskOrder\TaskAddEventSubscriber@handleStatistics'
        );
    }

    /**
     * @param TaskAddEvent $event
     * @param \Exception $exception
     */
    public function failed(TaskAddEvent $event, $exception)
    {
        Log::error("TaskAddEvent failed message :{$exception->getMessage()} orderNO:{$event->orderNo}");
    }

    /**
     * @return EmployerService
     */
    protected function getTradeEmployerBridge(){
        return new EmployerBridge(new EmployerService());
    }
}
