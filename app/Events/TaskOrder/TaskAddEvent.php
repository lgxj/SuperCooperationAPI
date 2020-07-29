<?php


namespace App\Events\TaskOrder;


use Illuminate\Queue\SerializesModels;

class TaskAddEvent
{
    use SerializesModels;

    public $orderNo = '';
    public $orderType = 0;

    public function __construct( $orderNo,$orderType = 0)
    {
        $this->orderNo = $orderNo;
        $this->orderType = $orderType;
    }
}
