<?php


namespace App\Console\Commands;


use App\Consts\GlobalConst;
use App\Consts\Trade\OrderConst;
use App\Exceptions\BusinessException;
use App\Models\Trade\Order\TaskOrder;
use App\Services\Trade\Order\Employer\ConfirmTaskOrderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TaskOvertimeComplete extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'TaskOrder:overtimeComplete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '任务超时完成';

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
     * @throws BusinessException
     */
    public function handle()
    {
        $now = Carbon::now();
        $deliverTime = Carbon::now()->addDays(-3);
        //超时完成，每小时运行一次，不用分页，更新完后根据状态会变更。分页太多消息会积压，后面数据上来了，可以暂时调整运行时间/limit数量
        $completeOrders = TaskOrder::where('deliver_time','<=',$deliverTime)
            ->where('order_state',OrderConst::EMPLOYER_STATE_DELIVERED)
            ->limit(500)
            ->select(['order_no','user_id'])
            ->get()
            ->keyBy('order_no')
            ->toArray();
        $count = count($completeOrders);
        echo "{$now} and {$deliverTime} 超时完成任务 num:{$count}\n";
        if(empty($completeOrders)){
            return;
        }
        foreach ($completeOrders as $orderNo=>$completeOrder){
            try {
                (new ConfirmTaskOrderService())->confirmComplete($orderNo, $completeOrder['user_id']);
            }catch (\Exception $e){
                \Log::error("超时完成 EXCEPTION",['order_no'=>$orderNo,'msg'=>$e->getMessage()]);
            }
        }
    }
}
