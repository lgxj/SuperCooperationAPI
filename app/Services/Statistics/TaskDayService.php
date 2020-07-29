<?php


namespace App\Services\Statistics;


use App\Models\Statistics\TaskDay;

/**
 * 任务按天统计
 *
 * Class TaskDayService
 * @package App\Services\Statistics
 */
class TaskDayService
{

    public function increment($field,$value = 1){
        if(empty($field) || $value <= 0){
            return false;
        }
        $day =  date('Ymd');
        $taskDay = TaskDay::where('day',$day)->first();
        if(empty($taskDay)){
            $taskDay = new TaskDay();
            $taskDay->day = $day;
            $taskDay->{$field} = $value;
            $taskDay->save();
        }else{
            $taskDay->increment($field,$value);
        }
        return true;
    }

    public function getDay($day = ''){
        if(empty($day)){
            $day =  date('Ymd');
        }
        $value = TaskDay::where('day',$day)->first();
        if(empty($value)){
            $value = new TaskDay();
            $value->day = $day;
            $value->publish_num = 0;
            $value->save();
        }
        $value = $value->toArray();
        $value['display_pay_total'] =  display_price($value['pay_total'] ?? 0);
        $value['display_withdraw_total'] =  display_price($value['withdraw_total'] ?? 0);
        $value['display_refund_total'] =  display_price($value['refund_total'] ?? 0);
        return $value;
    }

    public function search($filter = [], $pageSize = 10, $orderColumn = 'created_at',$direction ='desc'){
        $taskDayModel = TaskDay::getModel();
        $taskDayModel = $taskDayModel->when(isset($filter['day']) && $filter['day'] !== '',function ($query) use($filter){
            $query->where('day',$filter['day']);
        });
        $result = $taskDayModel->orderBy($orderColumn, $direction)->paginate($pageSize);
        collect($result->items())->map(function ($item) {
            $item['display_pay_total'] =  display_price($item['pay_total']);
            $item['display_withdraw_total'] =  display_price($item['withdraw_total']);
            $item['display_refund_total'] =  display_price($item['refund_total']);
            return $item;
        });
        return $result;
    }
}
