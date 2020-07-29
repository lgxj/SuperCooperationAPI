<?php


namespace App\Bridges\Statistics;


use App\Bridges\ScBridge;
use App\Services\Statistics\TaskDayService;

class TaskDayBridge extends ScBridge
{
    public function __construct(TaskDayService $taskDayService)
    {
        $this->service = $taskDayService;
    }
}
