<?php


namespace App\Listeners\Funds;



use App\Bridges\Trade\WithDrawBridge;
use App\Consts\MessageConst;
use App\Consts\Trade\WithDrawConst;
use App\Events\Funds\WithDrawEvent;
use App\Exceptions\BusinessException;
use App\Listeners\ScEventListener;
use App\Services\Trade\Fund\WithDrawService;
use Illuminate\Support\Facades\Log;
use Illuminate\Events\Dispatcher;

class WithDrawSubscriber extends ScEventListener
{

    /**
     * @param WithDrawEvent $event
     * @throws BusinessException
     */
    public function handleSendMessage(WithDrawEvent $event){
        $withDrawService = $this->getWithDrawBridge();
        $userId = $event->userId;
        $apply = $withDrawService->getById($userId,$event->withDrawId);
        $subType = MessageConst::TYPE_NOTICE_WITHDRAW;
        if(empty($apply)){
            \Log::error("with draw  failed");
            return;
        }
        if($apply['status'] ==  WithDrawConst::STATUS_COMPLETE){
            $this->getTaskDayBridge()->increment('withdraw_total',$apply['withdraw_money']);
            $disPlayPrice = display_price($apply['withdraw_money']);
            $content = "提现{$disPlayPrice}元，已经到账了({$apply['withdraw_desc']})";
            single_notice_send_message(0,$userId,$subType,'提现到账了',$content,$apply['withdraw_no']);
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
            'App\Events\Funds\WithDrawEvent',
            'App\Listeners\Funds\WithDrawSubscriber@handleSendMessage'
        );
    }

    /**
     * @param WithDrawEvent $event
     * @param \Exception $exception
     */
    public function failed(WithDrawEvent $event, $exception)
    {
        Log::error("WithDrawEvent failed message :{$exception->getMessage()} withDrawId:{$event->withDrawId}");
    }

    /**
     * @return WithDrawService
     */
    public function getWithDrawBridge(){
        return new WithDrawBridge(new WithDrawService());
    }
}
