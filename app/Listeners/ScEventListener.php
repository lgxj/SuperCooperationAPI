<?php


namespace App\Listeners;


use App\Bridges\Statistics\TaskDayBridge;
use App\Services\Statistics\TaskDayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScEventListener implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue,SerializesModels,Queueable;

    public function __construct()
    {
    }

    /**
     * @return TaskDayService
     */
    public function getTaskDayBridge(){
        return new TaskDayBridge(new TaskDayService());
    }
}
