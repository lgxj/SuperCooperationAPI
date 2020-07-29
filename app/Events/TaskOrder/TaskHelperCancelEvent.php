<?php


namespace App\Events\TaskOrder;


use Illuminate\Queue\SerializesModels;

/**
 * 帮手取消任务事件
 *
 * Class TaskHelperCancelEvent
 * @package App\Events\TaskOrder
 */
class TaskHelperCancelEvent
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
