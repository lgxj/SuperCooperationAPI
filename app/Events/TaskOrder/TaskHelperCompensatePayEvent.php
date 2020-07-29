<?php


namespace App\Events\TaskOrder;


use Illuminate\Queue\SerializesModels;

class TaskHelperCompensatePayEvent
{
    use SerializesModels;

    public $orderNo = '';

    public $receiverUserId = 0;

    public function __construct($orderNo, $receiverUserId)
    {
        $this->orderNo = $orderNo;
        $this->receiverUserId = $receiverUserId;
    }
}
