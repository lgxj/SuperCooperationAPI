<?php


namespace App\Console\Commands;


use App\Consts\GlobalConst;
use App\Consts\MessageConst;
use App\Consts\Trade\OrderConst;
use App\Exceptions\BusinessException;
use App\Models\Trade\Order\TaskOrder;
use App\Services\Message\OrderMessageService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TaskSendMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'TaskOrder:taskSendMessage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '消息延时发送';

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

        //推送快延期的任务给帮手和雇主
        $now = Carbon::now();
        $delayTime = 60;
        $expireDate = Carbon::now()->addMinutes($delayTime);//向前推一小时
        $orders = TaskOrder::where('end_time','>=',$now)
                ->where('end_time','<=',$expireDate)
                ->whereIn('order_state',[OrderConst::EMPLOYER_STATE_UN_RECEIVE,OrderConst::EMPLOYER_STATE_UN_CONFIRM,OrderConst::EMPLOYER_STATE_RECEIVE,OrderConst::EMPLOYER_STATE_DELIVERED])
                ->limit(700)
                ->select(['order_no','order_state'])
                ->get()
                ->keyBy('order_no')
                ->toArray();

        foreach ($orders as $orderNo => $order){
           if(in_array($order['order_state'],OrderConst::helperCanReceiveList())){
               single_order_send_message(0,MessageConst::TYPE_ORDER_EMPLOYER_NO_HELPER,$orderNo);
           }elseif($order['order_state'] == OrderConst::EMPLOYER_STATE_RECEIVE){
               single_order_send_message(0,MessageConst::TYPE_ORDER_HELPER_DEADLINE,$orderNo);
           }elseif($order['order_state'] == OrderConst::EMPLOYER_STATE_DELIVERED){
               single_order_send_message(0,MessageConst::TYPE_ORDER_EMPLOYER_DEADLINE,$orderNo);
           }
        }

        $expireDate2 = Carbon::now()->addMinutes(convert_negative_number($delayTime));//向后推一小时
        //推送过期任务给帮手 ,每小时运行一次，不用分页，更新完后根据状态会变更。分页太多消息会积压，后面数据上来了，可以暂时调整运行时间
        $expireOrders = TaskOrder::where('end_time','<=',$now)
            ->where('end_time','>=',$expireDate2)
            ->where('order_state',OrderConst::EMPLOYER_STATE_RECEIVE)
            ->limit(700)
            ->select(['order_no','order_state'])
            ->get()
            ->keyBy('order_no')
            ->toArray();
        foreach ($expireOrders as $orderNo => $order){
           if($order['order_state'] == OrderConst::EMPLOYER_STATE_RECEIVE){
                single_order_send_message(0,MessageConst::TYPE_ORDER_HELPER_OVERTIME,$orderNo);
            }
        }
        $delayTotal = count($orders);
        $expireTotal = count($expireOrders);
        echo $now."  and {$expireDate}  and {$expireDate2} task send message delay orders:{$delayTotal} expire orders:{$expireTotal}\n";

    }



}
