<?php


namespace App\Services\Trade\Order\Helper;


use App\Consts\GlobalConst;
use App\Consts\Trade\OrderConst;
use App\Models\Trade\Order\ReceiverOrder;
use App\Models\Trade\Order\TaskOrder;
use App\Services\ScService;
use App\Services\Trade\Traits\ServiceTrait;

/**
 * 帮手-接单列表
 *
 * Class ListOrderService
 * @package App\Services\Trade\Order\Helper
 */
class ListOrderService extends ScService
{

    use ServiceTrait;
    const LIST_ALL = 0;
    const LIST_UN_COMPLETE = 1;
    const LIST_COMPLETE = 2;

    public function listByUser ($userId,$lng,$lat,$state = self::LIST_ALL,$page =  1,$pageSize = GlobalConst::PAGE_SIZE){
        $receiverOrder = ReceiverOrder::where(['user_id'=>$userId]);
        $receiverOrder->when($state > 0 ,function ($query) use ($state){
            if($state == self::LIST_UN_COMPLETE){
                $query->whereIn('receive_state',OrderConst::helperUnCompleteList());
            }elseif($state == self::LIST_COMPLETE){
                $query->whereIn('receive_state',OrderConst::helperCompleteList());
            }
        });
        $receiverOrder->select(['order_no','quoted_price','receive_state','comment_id','order_type','user_id','cancel_compensate_status'])->orderBy('receiver_id','desc');//利用覆盖索引+延迟关联解决（file sort+limit page）查询速度的问题
        $receiveOrders = $receiverOrder->forpage($page,$pageSize)->get()->keyBy('order_no')->toArray();
        $orderNos = array_keys($receiveOrders);
        if(empty($orderNos)){
            return [];
        }
        $orders = $this->getDetailService()->getOrders($orderNos,'',false,true,true,true);
        $return = [];
        foreach ($receiveOrders as $orderNo =>$receiveOrder){
            $value = $orders[$orderNo] ?? [];
            if(empty($value)){
                continue;
            }
            $addressList = $value['address_list'];
            $addressLng = $addressList[0]['lng'] ?? '';
            $addressLat = $addressList[0]['lat'] ?? '';
            $mockYunTu = [];
            if($lng && $addressLng){
                $mockYunTu['_distance'] = distance($lat,$lng,$addressLat,$addressLng,'m');
                $mockYunTu['_location'] = "{$lng},{$lat}";
            }
            format_receiver_order($receiveOrder,[],$value);
            $tmp =  format_task_order($value,$mockYunTu);
            $tmp['receive_comment_id'] = $receiveOrder['comment_id'];
            $tmp['receive_state'] = $receiveOrder['receive_state'];
            $tmp['quoted_price'] = $receiveOrder['quoted_price'];
            $tmp['bottom_receive_state'] = $receiveOrder['bottom_receive_state'];
            $tmp['display_receive_state'] = $receiveOrder['display_receive_state'];
            $tmp['is_self'] = $receiveOrder['is_self'];
            $tmp['display_quoted_price'] = $receiveOrder['display_quoted_price'];
            $tmp['time_valid'] = $receiveOrder['time_valid'] ?? [];
            $tmp['cancel_compensate_status'] = $receiveOrder['cancel_compensate_status'];
            $return[] = $tmp;

        }
        return $return;
    }

    public function getUserByOrderNos($userId,array $orderNos){
        if($userId <= 0 || empty($orderNos)){
            return [];
        }
        return ReceiverOrder::where(['user_id'=>$userId])->whereIn('order_no',$orderNos)->get()->keyBy('order_no')->toArray();
    }

    public function getReceivesByOrderNos(array $orderNos){
        if(empty($orderNos)){
            return [];
        }
        $receivers = ReceiverOrder::whereIn('order_no',$orderNos)->get()->groupBy('order_no');
        return $receivers->toArray();
    }

    public function getReceiveByOrderNo($orderNo,$uid){
        if(empty($orderNo) || $uid <= 0){
            return null;
        }
        $receiver = ReceiverOrder::where(['order_no'=>$orderNo,'user_id'=>$uid])->first();
        return $receiver ? $receiver->toArray() : null;
    }
}
