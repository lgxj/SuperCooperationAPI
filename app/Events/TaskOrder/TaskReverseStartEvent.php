<?php


namespace App\Events\TaskOrder;


use Illuminate\Queue\SerializesModels;

/**
 * 帮手取消，任务回退到开始事件
 *
 * Class TaskReverseStartEvent
 * @package App\Events\TaskOrder
 */
class TaskReverseStartEvent
{
    use SerializesModels;

    public $orderNo = '';


    public function __construct( $orderNo)
    {
        $this->orderNo = $orderNo;
    }
}
