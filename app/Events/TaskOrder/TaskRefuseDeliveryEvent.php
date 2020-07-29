<?php


namespace App\Events\TaskOrder;


use Illuminate\Events\Dispatcher;
use Illuminate\Queue\SerializesModels;

class TaskRefuseDeliveryEvent
{
    use SerializesModels;

    public $orderNo = '';

    public function __construct( $orderNo)
    {
        $this->orderNo = $orderNo;
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


}
