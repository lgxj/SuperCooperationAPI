<?php

namespace App\Console\Commands;

use App\Consts\GlobalConst;
use App\Consts\Trade\OrderConst;
use App\Models\Pool\YunTuTableData;
use App\Models\Trade\Order\Search;
use App\Services\Pool\YunTuService;
use App\Services\Trade\Order\Employer\EmployerService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckYunTuEmployerMap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'TaskOrder:checkYunTuEmployerMap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '检查雇主云图数据是否完整,防止事件队列丢丢';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws \App\Exceptions\BusinessException
     */
    public function handle()
    {
        $now = Carbon::now();
        $createdAt = Carbon::now()->addMinutes(-15);
        $orderNos = Search::where('created_at','>=',$createdAt)
            ->select('order_no')
            ->orderBy('created_at','desc')
            ->limit(200)
            ->pluck('order_no')
            ->toArray();
        echo $now." and  {$createdAt} order num:".count($orderNos)."\n";
        if($orderNos){
            $employerService = new EmployerService();
            $tableOrders = YunTuTableData::where('business_type',YunTuService::BUSINESS_TYPE_EMPLOYER)
                ->whereIn('business_no',$orderNos)
                ->select('table_data_id','business_no')
                ->pluck('table_data_id','business_no')
                ->toArray();

            foreach ($orderNos as $orderNo){
                if(!isset($tableOrders[$orderNo])){
                    echo "orderNo not exist {$orderNo}\n";
                    $employerService->saveEmployerYuTuAddress($orderNo,true);
                }
            }
        }
    }
}
