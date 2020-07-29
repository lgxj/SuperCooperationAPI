<?php


namespace App\Events\TaskOrder;


use Illuminate\Queue\SerializesModels;

/**
 * 帮手接单事件
 *
 * Class TaskReceiveEvent
 * @package App\Events\TaskOrder
 */
class TaskReceiveEvent
{
    use SerializesModels;

    public $orderNo = '';

    public $receiverUserId = 0;

    public $orderType = 0;

    public function __construct($orderNo, $receiverUserId,$orderType=0)
    {
        $this->orderNo = $orderNo;
        $this->receiverUserId = $receiverUserId;
        $this->orderType = $orderType;
    }
}
