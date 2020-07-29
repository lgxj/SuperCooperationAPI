<?php


namespace App\Console\Commands;


use App\Consts\MessageConst;
use App\Consts\Trade\OrderConst;
use App\Exceptions\BusinessException;
use App\Models\Trade\Order\TaskOrder;
use App\Services\Trade\Order\Employer\CancelOrderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TaskOverdue  extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'TaskOrder:Overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '任务大厅逾期任务取消';

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
        $overrideTime = Carbon::now()->addMinutes(-60);
        //任务逾期未接单，每小时运行一次，不用分页，更新完后根据状态会变更。分页太多消息会积压，后面数据上来了，可以暂时调整运行时间/limit数量
        $overrideOrders = TaskOrder::where('end_time','<=',$overrideTime)
            ->whereIn('order_state',[OrderConst::EMPLOYER_STATE_UN_RECEIVE,OrderConst::EMPLOYER_STATE_UN_CONFIRM])
            ->limit(500)
            ->select(['order_no','user_id'])
            ->get()
            ->keyBy('order_no')
            ->toArray();
        $count = count($overrideOrders);
        echo "{$now} and {$overrideTime} 逾期未接单任务 num:{$count}\n";
        if(empty($overrideOrders)){
            return;
        }
        foreach ($overrideOrders as $orderNo=>$overrideOrder){
            try {
                (new CancelOrderService())->cancel($orderNo, $overrideOrder['user_id'], OrderConst::CANCEL_TYPE_EMPLOYER_SYSTEM, '任务逾期未接单取消');
                single_order_send_message(0, MessageConst::TYPE_ORDER_EMPLOYER_OVERDUE_CANCEL, $orderNo);
            }catch (\Exception $e){
                \Log::error("任务逾期未接单取消 EXCEPTION",['order_no'=>$orderNo,'msg'=>$e->getMessage()]);
            }

        }
    }

}
