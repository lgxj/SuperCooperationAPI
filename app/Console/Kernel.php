<?php

namespace App\Console;

use App\Console\Commands\CheckYunTuEmployerMap;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CheckYunTuEmployerMap::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $commandPath = storage_path().DIRECTORY_SEPARATOR.'command'.DIRECTORY_SEPARATOR;
        $commandFile = $commandPath.'command.txt';
        $schedule->command('TaskOrder:checkYunTuEmployerMap')->everyFifteenMinutes()->sendOutputTo($commandPath.'checkYunTuEmployerMap.txt',true);
        $schedule->command('TaskOrder:taskSendMessage')->hourly()->sendOutputTo($commandPath.'taskSendMessage.txt',true);
        $schedule->command('TaskOrder:overtimeComplete')->hourly()->sendOutputTo($commandPath.'overtimeComplete.txt',true);
        $schedule->command('TaskOrder:Overdue')->hourly()->sendOutputTo($commandPath.'overdue.txt',true);
        //$schedule->command('sc:ok')->everyMinute()->sendOutputTo($commandPath.'heart.txt',true);
        $schedule->command('horizon:snapshot')->everyFiveMinutes();

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
