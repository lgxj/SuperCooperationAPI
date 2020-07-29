<?php


namespace App\Events\TaskOrder;


use Illuminate\Queue\SerializesModels;

/**
 * 任务开始（雇主首次支付成功）
 *
 * Class TaskStartEvent
 * @package App\Events\TaskOrder
 */
class TaskStartEvent
{
    use SerializesModels;

    public $orderNo = '';

    public $notifyPrice = 0;

    public function __construct( $orderNo,$notifyPrice = 0)
    {
        $this->orderNo = $orderNo;
        $this->notifyPrice = $notifyPrice;
    }

}
