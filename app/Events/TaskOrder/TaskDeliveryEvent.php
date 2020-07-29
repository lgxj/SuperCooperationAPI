<?php


namespace App\Events\TaskOrder;


use Illuminate\Queue\SerializesModels;

/**
 * 任务交付事件
 *
 * Class TaskDeliveryEvent
 * @package App\Events\TaskOrder
 */
class TaskDeliveryEvent
{
    use SerializesModels;

    public $orderNo = '';

    public $receiverUserId = 0;

    public function __construct( $orderNo,$receiverUserId)
    {
        $this->orderNo = $orderNo;
        $this->receiverUserId = $receiverUserId;
    }
}
