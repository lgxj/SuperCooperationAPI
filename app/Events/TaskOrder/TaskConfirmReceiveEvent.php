<?php


namespace App\Events\TaskOrder;


use Illuminate\Queue\SerializesModels;

/**
 * 竞价任务确认雇主事件
 *
 * Class TaskConfirmReceiveEvent
 * @package App\Events\TaskOrder
 */
class TaskConfirmReceiveEvent
{
    use SerializesModels;

    public $orderNo = '';

    public $receiverUserId = 0;

    public $notifyPrice = 0;

    public function __construct( $orderNo,$receiverUserId,$notifyPrice = 0)
    {
        $this->orderNo = $orderNo;
        $this->receiverUserId = $receiverUserId;
        $this->notifyPrice = $notifyPrice;
    }
}
