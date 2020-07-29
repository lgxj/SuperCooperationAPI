<?php


namespace App\Events\TaskOrder;


use Illuminate\Queue\SerializesModels;

/**
 * 任务完成事件
 *
 * Class TaskCompleteEvent
 * @package App\Events\TaskOrder
 */
class TaskCompleteEvent
{
    use SerializesModels;

    public $orderNo = '';

    public function __construct( $orderNo)
    {
        $this->orderNo = $orderNo;
    }
}
