<?php


namespace App\Services\Trade\Order\Admin;


use App\Bridges\User\UserBridge;
use App\Consts\Trade\OrderConst;
use App\Consts\Trade\PayConst;
use App\Consts\UserConst;
use App\Models\Trade\Order\ReceiverOrder;
use App\Models\Trade\Order\TaskOrder;
use App\Services\User\UserService;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 帮手后台管理
 *
 * Class HelperManagerService
 * @package App\Services\Trade\Order\Admin
 */
class HelperManagerService
{

    public function search($filter = [], $pageSize = 10, $orderColumn = 'created_at', $direction = 'desc'){
        $taskModel = ReceiverOrder::getModel();
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

        $taskModel = $taskModel->join('order','order_receiver.order_no','=','order.order_no')->select('order_receiver.*','order_state','order_name','order.order_type','category','pay_price','origin_price','refund_state');
        $taskModel = $taskModel->when(!empty($filter['user_id']), function ($query) use ($filter) {
            $query->where('order_receiver.user_id', $filter['user_id']);
        })->when(!empty($userIds), function ($query) use ($userIds) {
            $query->whereIn('order_receiver.user_id', $userIds);
        })->when(!empty($filter['create_time']),function ($query) use($filter){
            $query->where('order_receiver.created_at','>=',$filter['created_time'][0])->where('order_receiver.created_at','<=',$filter['created_time'][1]);
        })->when(!empty($filter['order_name']),function ($query) use($filter){
            $query->where('order.order_name',$filter['order_name']);
        })->when(!empty($filter['order_no']),function ($query) use($filter){
            $query->where('order_receiver.order_no',$filter['order_no']);
        })->when(!empty($filter['receive_state']),function ($query) use($filter){
            $query->where('order_receiver.receive_state',$filter['receive_state']);
        })->when(!empty($filter['order_type']),function ($query) use($filter){
            $query->where('order_receiver.order_type',$filter['order_type']);
        });

        $result = $taskModel->orderBy($orderColumn, $direction)->paginate($pageSize);
        $receivers = collect($result->items());
        $userIds = array_unique($receivers->pluck('user_id')->toArray());
        $users = $userBridge->users($userIds);
        collect($result->items())->map(function ($item) use($users) {
            $user = $users[$item['user_id']] ?? [];
            $item['user_avatar'] = $user['user_avatar'];
            $item['user_name'] = $user['user_name'];
            $item['helper_level'] = $user['helper_level'];
            $item['display_order_type'] = OrderConst::getTypeList($item['order_type']);
            $item['display_employer_order_state'] = OrderConst::getEmployerStateList($item['order_state']);
            $item['display_employer_pay_state'] = PayConst::getStateList($item['pay_state']);
            $item['display_cancel_type'] = OrderConst::getCancelTypeList($item['cancel_type']);
            $item['display_receive_state'] = OrderConst::getCancelTypeList($item['receive_state']);
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
}
