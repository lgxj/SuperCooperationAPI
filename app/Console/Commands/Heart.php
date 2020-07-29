<?php


namespace App\Console\Commands;


use Carbon\Carbon;
use Illuminate\Console\Command;

class Heart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sc:ok';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '心跳检测';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        echo Carbon::now()." ok\n";
    }
}
