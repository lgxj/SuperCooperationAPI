<?php


namespace App\Events\TaskOrder;


use Illuminate\Queue\SerializesModels;

class TaskUpdateEvent
{
    use SerializesModels;

    public $orderNo = '';

    public $taskState = null;

    public function __construct( $orderNo,$taskState)
    {
        $this->orderNo = $orderNo;
        $this->taskState = $taskState;
    }
}
