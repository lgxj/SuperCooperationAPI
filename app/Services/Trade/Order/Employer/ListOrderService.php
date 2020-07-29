<?php


namespace App\Services\Trade\Order\Employer;


use App\Consts\GlobalConst;
use App\Consts\Trade\OrderConst;
use App\Models\Trade\Order\ReceiverOrder;
use App\Models\Trade\Order\TaskOrder;
use App\Services\ScService;
use App\Services\Trade\Traits\ServiceTrait;

/**
 * 雇主发单列表管理
 *
 * Class ListOrderService
 * @package App\Services\Trade\Order\Employer
 */
class ListOrderService extends ScService
{
    use ServiceTrait;

    const LIST_ALL = 0;
    const LIST_UN_COMPLETE = 1;
    const LIST_COMPLETE = 2;

    public function listByUser ($userId,$state = self::LIST_ALL,$page =  1,$pageSize = GlobalConst::PAGE_SIZE){
       $taskOrder = TaskOrder::where(['user_id'=>$userId]);
       $taskOrder->when($state > 0 ,function ($query) use ($state){
            if($state == self::LIST_UN_COMPLETE){
                $query->whereIn('order_state',OrderConst::employerUnCompleteList());
            }elseif($state == self::LIST_COMPLETE){
                $query->whereIn('order_state',OrderConst::employerCompleteList());
            }
       });
       $taskOrder->select('order_no')->orderBy('order_id','desc');//利用覆盖索引+延迟关联解决（file sort+limit page）查询速度的问题
       $orderNos = $taskOrder->forpage($page,$pageSize)->pluck('order_no')->toArray();
       if(empty($orderNos)){
           return [];
       }
        $orders = $this->getDetailService()->getOrders($orderNos,'',false,true,true,true,$userId);
        $return = [];
        foreach ($orderNos as $orderNo){
            $value = $orders[$orderNo] ?? [];
            if(empty($value)){
                continue;
            }
            $tmp = format_task_order($value);
            $tmp['competition_total'] = 0;
            if($value['order_type'] == OrderConst::TYPE_COMPETITION && in_array($value['order_state'],[OrderConst::EMPLOYER_STATE_UN_CONFIRM])){
                $tmp['competition_total'] = $this->countReceiver($value['order_no']);
            }
            $return[] = $tmp;
        }
        return $return;
    }

    public function countReceiver($orderNo){
       return ReceiverOrder::where('order_no',$orderNo)->count();
    }
}
