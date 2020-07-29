<?php


namespace App\Listeners\TaskOrder;


use App\Bridges\Trade\CompensateBridge;
use App\Bridges\Trade\DetailTaskOrderBridge;
use App\Bridges\User\UserBridge;
use App\Consts\MessageConst;
use App\Consts\Trade\OrderConst;
use App\Consts\Trade\PayConst;
use App\Events\TaskOrder\TaskHelperCancelEvent;
use App\Exceptions\BusinessException;
use App\Listeners\ScEventListener;
use App\Services\Trade\Fund\CompensateService;
use App\Services\Trade\Order\Employer\DetailTaskOrderService;
use App\Services\Trade\Order\Helper\HelperService;
use App\Services\User\UserService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class TaskHelperCancelEventSubscriber extends ScEventListener
{
    /**
     * @param TaskHelperCancelEvent $event
     * @throws BusinessException
     */
    public function handleSendMessage(TaskHelperCancelEvent $event){
        $orderNo = $event->orderNo;
        $userService = $this->getUserBridge();
        $this->getTaskDayBridge()->increment('helper_cancel_num',1);
        if((new HelperService())->countWeekCancelTotal($event->receiverUserId) > OrderConst::HELPER_MAX_CANCEL_WEEK) {
            $userService->updateUserStatus($event->receiverUserId, 1);
        }
        single_order_send_message(0,MessageConst::TYPE_ORDER_HELPER_CANCEL,$orderNo);
    }


    /**
     * 为订阅者注册监听器.
     *
     * @param  Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'App\Events\TaskOrder\TaskHelperCancelEvent',
            'App\Listeners\TaskOrder\TaskHelperCancelEventSubscriber@handleSendMessage'
        );
    }

    /**
     * @param TaskHelperCancelEvent $event
     * @param \Exception $exception
     */
    public function failed(TaskHelperCancelEvent $event, $exception)
    {
        Log::error("TaskHelperCancelEvent failed message :{$exception->getMessage()} orderNO:{$event->orderNo}");
    }

    /**
     * @return UserService
     */
    public function getUserBridge(){
        return new UserBridge(new UserService());
    }


}
