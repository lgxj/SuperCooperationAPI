<?php


namespace App\Events\TaskOrder;


use Illuminate\Queue\SerializesModels;

/**
 * 雇主取消任务事件
 *
 * Class TaskEmployerCancelEvent
 * @package App\Events\TaskOrder
 */
class TaskEmployerCancelEvent
{
    use SerializesModels;

    public $orderNo = '';

    public function __construct( $orderNo)
    {
        $this->orderNo = $orderNo;
    }
}
