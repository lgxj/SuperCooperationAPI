<?php


namespace App\Services\Trade\Order\Admin;


use App\Bridges\Trade\DetailTaskOrderBridge;
use App\Bridges\User\UserBridge;
use App\Consts\Trade\OrderConst;
use App\Consts\Trade\PayConst;
use App\Consts\UserConst;
use App\Models\Trade\Order\TaskOrder;
use App\Services\Trade\Order\Employer\DetailTaskOrderService;
use App\Services\User\UserService;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 雇主后台管理
 *
 * Class EmployerManagerService
 * @package App\Services\Trade\Order\Admin
 */
class EmployerManagerService
{

    public function search($filter = [], $pageSize = 10, $orderColumn = 'created_at', $direction = 'desc'){
        $taskModel = TaskOrder::getModel();
        $userIds = [];
        $userBridge = $this->getUserBridge();
        if(!empty($filter['user_name'])){
            $userGrant = $userBridge->findByGrantType(UserConst::GRANT_LOGIN_TYPE_PHONE,$filter['user_name']);
            if($userGrant){
                $userIds[] = $userGrant['user_id'];
            }
            if (empty($userIds)) {
                $users = $userBridge->search(['user_name'=>$filter['user_name']]);
                $userIds = array_keys($users);
            }

            if(empty($userIds)){
                return new LengthAwarePaginator([],0,$pageSize);
            }
        }

        $taskModel = $taskModel->when(!empty($filter['user_id']), function ($query) use ($filter) {
            $query->where('user_id', $filter['user_id']);
        })->when(!empty($userIds), function ($query) use ($userIds) {
            $query->whereIn('user_id', $userIds);
        })->when(!empty($filter['order_time']),function ($query) use($filter){
                $query->where('start_time','<=',$filter['order_time'][1])->where('end_time', '>=', $filter['order_time'][0]);
        })->when(!empty($filter['create_time']),function ($query) use($filter){
            $query->where('created_at','>=',$filter['created_time'][0])->where('created_at','<=',$filter['created_time'][1]);
        })->when(isset($filter['order_state']) && $filter['order_state'] !== '',function ($query) use($filter){
            $query->where('order_state',$filter['order_state']);
        })->when(!empty($filter['order_name']),function ($query) use($filter){
            $query->where('order_name',$filter['order_name']);
        })->when(!empty($filter['order_no']),function ($query) use($filter){
            $query->where('order_no',$filter['order_no']);
        })->when(isset($filter['order_type']) && $filter['order_type'] !== '',function ($query) use($filter){
            $query->where('order_type',$filter['order_type']);
        })->when(!empty($filter['category']),function ($query) use($filter){
            $query->where('category',$filter['category']);
        });

        $result = $taskModel->orderBy($orderColumn, $direction)->paginate($pageSize);
        $tasks = collect($result->items());
        $userIds = array_unique($tasks->pluck('user_id')->toArray());
        $users = $userBridge->users($userIds);
        collect($result->items())->map(function ($item) use($users) {
            $user = $users[$item['user_id']] ?? [];
            $item['user_avatar'] = $user['user_avatar'];
            $item['user_name'] = $user['user_name'];
            $item['display_order_type'] = OrderConst::getTypeList($item['order_type']);
            $item['display_employer_order_state'] = OrderConst::getEmployerStateList($item['order_state']);
            $item['display_employer_pay_state'] = PayConst::getStateList($item['pay_state']);
            $item['display_cancel_type'] = OrderConst::getCancelTypeList($item['cancel_type']);
            return $item;
        });

        return $result;
    }



    /**
     * @return UserService
     */
    protected function getUserBridge(){
        return new UserBridge(new UserService());
    }

    protected function getDetailBridge(){
        return new DetailTaskOrderBridge(new DetailTaskOrderService());
    }
}
