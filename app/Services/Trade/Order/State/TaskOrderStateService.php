<?php


namespace App\Services\Trade\Order\State;


use App\Consts\Trade\OrderConst;
use App\Consts\UserConst;
use App\Services\Trade\Order\BaseTaskOrderService;
use Illuminate\Database\Eloquent\Model;

/**
 * 任务状态变更器
 *
 * Class TaskOrderStateService
 * @package App\Services\Trade\Order\State
 */
class TaskOrderStateService extends BaseTaskOrderService
{

    public function successComplete(Model $taskOrder ,Model $receiver){
        $this->addOrderStateChange($receiver['user_id'],$receiver['order_no'],$receiver['receive_state'],OrderConst::HELPER_STATE_COMPLETE,UserConst::TYPE_HELPER);
        $this->updateReceiverOrderState($receiver,OrderConst::HELPER_STATE_COMPLETE);
        $this->addOrderStateChange($taskOrder['user_id'],$taskOrder['order_no'],$taskOrder['order_state'],OrderConst::EMPLOYER_STATE_COMPLETE,UserConst::TYPE_EMPLOYER);
        $this->updateEmployerOrderState($taskOrder,OrderConst::EMPLOYER_STATE_COMPLETE);
    }

    public function successConfirmReceiver(Model $taskOrder , Model $receiver){
        $taskOrder->helper_user_id = $receiver->user_id;
        $receiver->is_selected = 1;
        $receiver->order_type = $taskOrder['order_type'];
        $this->addOrderStateChange($receiver['user_id'],$receiver['order_no'],$receiver['receive_state'],OrderConst::HELPER_STATE_RECEIVE,UserConst::TYPE_HELPER);
        $this->updateReceiverOrderState($receiver,OrderConst::HELPER_STATE_RECEIVE);
        $this->addOrderStateChange($taskOrder['user_id'],$taskOrder['order_no'],$taskOrder['order_state'],OrderConst::EMPLOYER_STATE_RECEIVE,UserConst::TYPE_EMPLOYER);
        $this->updateEmployerOrderState($taskOrder,OrderConst::EMPLOYER_STATE_RECEIVE);
    }

    public function successDelivery(Model $taskOrder ,Model $receiver){
        $this->addOrderStateChange($receiver['user_id'],$receiver['order_no'],$receiver['receive_state'],OrderConst::HELPER_STATE_DELIVERED,UserConst::TYPE_HELPER);
        $this->updateReceiverOrderState($receiver,OrderConst::HELPER_STATE_DELIVERED);
        $this->addOrderStateChange($taskOrder['user_id'],$taskOrder['order_no'],$taskOrder['order_state'],OrderConst::EMPLOYER_STATE_DELIVERED,UserConst::TYPE_EMPLOYER);
        $this->updateEmployerOrderState($taskOrder,OrderConst::EMPLOYER_STATE_DELIVERED);
    }

    public function successEmployerFirstPay(Model $taskOrder){
       if($taskOrder['order_type'] == OrderConst::TYPE_COMPETITION){
           $this->addOrderStateChange($taskOrder['user_id'], $taskOrder['order_no'], $taskOrder['order_state'], OrderConst::EMPLOYER_STATE_UN_CONFIRM, UserConst::TYPE_EMPLOYER);
           $this->updateEmployerOrderState($taskOrder, OrderConst::EMPLOYER_STATE_UN_CONFIRM);
       }else {
           $this->addOrderStateChange($taskOrder['user_id'], $taskOrder['order_no'], $taskOrder['order_state'], OrderConst::EMPLOYER_STATE_UN_RECEIVE, UserConst::TYPE_EMPLOYER);
           $this->updateEmployerOrderState($taskOrder, OrderConst::EMPLOYER_STATE_UN_RECEIVE);
       }
    }

    public function successEmployerReceiver(Model $taskOrder){
        if($taskOrder['order_type'] == OrderConst::TYPE_GENERAL){
            $this->addOrderStateChange($taskOrder['user_id'], $taskOrder['order_no'], $taskOrder['order_state'], OrderConst::EMPLOYER_STATE_RECEIVE, UserConst::TYPE_EMPLOYER);
            $this->updateEmployerOrderState($taskOrder, OrderConst::EMPLOYER_STATE_RECEIVE);
        }
    }
    public function successHelperReceiver(Model $taskOrder, Model $receiver){
        $receiver->order_type = $taskOrder['order_type'];
        if($taskOrder['order_type'] == OrderConst::TYPE_COMPETITION){
            $this->addOrderStateChange($receiver['user_id'], $receiver['order_no'], $receiver['receive_state'], OrderConst::HELPER_STATE_EMPLOYER_UN_CONFIRM, UserConst::TYPE_HELPER);
            $this->updateReceiverOrderState($receiver,OrderConst::HELPER_STATE_EMPLOYER_UN_CONFIRM);
        }else {
            $receiver->is_selected = 1;
            $this->addOrderStateChange($receiver['user_id'], $receiver['order_no'], $receiver['receive_state'], OrderConst::HELPER_STATE_RECEIVE, UserConst::TYPE_HELPER);
            $this->updateReceiverOrderState($receiver,OrderConst::HELPER_STATE_RECEIVE);
        }
    }

    public function cancelHelper(Model $receiver,$cancelType = OrderConst::CANCEL_TYPE_EMPLOYER_COMPETITION_FAIL){
        $receiver->is_selected = 0;
        $this->addOrderStateChange($receiver['user_id'],$receiver['order_no'],$receiver['receive_state'],OrderConst::HELPER_STATE_CANCEL,UserConst::TYPE_HELPER);
        $this->updateReceiverOrderState($receiver,OrderConst::HELPER_STATE_CANCEL,$cancelType);
    }

    public function cancelEmployer(Model $taskOrder,$cancelType = OrderConst::CANCEL_TYPE_EMPLOYER_COMPETITION_FAIL){
        $this->addOrderStateChange($taskOrder['user_id'],$taskOrder['order_no'],$taskOrder['order_state'],OrderConst::EMPLOYER_STATE_CANCEL,UserConst::TYPE_EMPLOYER);
        $this->updateEmployerOrderState($taskOrder,OrderConst::EMPLOYER_STATE_CANCEL,$cancelType);
    }

    /**
     * 帮手取消订单后，任务回退到待接单状态
     *
     * @param Model $taskOrder
     */
    public function reverseOrderReceive(Model $taskOrder){
        $taskOrder->helper_user_id = 0;
        if($taskOrder['order_type'] == OrderConst::TYPE_COMPETITION){
            $this->addOrderStateChange($taskOrder['user_id'], $taskOrder['order_no'], $taskOrder['order_state'], OrderConst::EMPLOYER_STATE_UN_CONFIRM, UserConst::TYPE_EMPLOYER);
            $this->updateEmployerOrderState($taskOrder, OrderConst::EMPLOYER_STATE_UN_CONFIRM);
        }else{
            $this->addOrderStateChange($taskOrder['user_id'], $taskOrder['order_no'], $taskOrder['order_state'], OrderConst::EMPLOYER_STATE_UN_RECEIVE, UserConst::TYPE_EMPLOYER);
            $this->updateEmployerOrderState($taskOrder, OrderConst::EMPLOYER_STATE_UN_RECEIVE);
        }
    }

    public function refuseDelivery(Model $taskOrder,Model $receiver){
        $this->addOrderStateChange($taskOrder['user_id'], $taskOrder['order_no'], $taskOrder['order_state'], OrderConst::EMPLOYER_STATE_REFUSE_DELIVERY, UserConst::TYPE_EMPLOYER);
        $this->updateEmployerOrderState($taskOrder, OrderConst::EMPLOYER_STATE_REFUSE_DELIVERY);
        $this->addOrderStateChange($receiver['user_id'],$receiver['order_no'],$receiver['receive_state'],OrderConst::HELPER_STATE_REFUSE_DELIVERY,UserConst::TYPE_HELPER);
        $this->updateReceiverOrderState($receiver,OrderConst::HELPER_STATE_REFUSE_DELIVERY);
    }


    public function retryDelivery(Model $taskOrder,Model $receiver){
        return $this->successDelivery($taskOrder,$receiver);
    }

    public function cancelAllHelper($orderNo,$cancelType = OrderConst::CANCEL_TYPE_EMPLOYER_COMPETITION_FAIL, $exclusiveUserId = 0){
        $orderQuotedList = $this->getReceiveModel()->getOrderAllReceiverList($orderNo);
        $orderQuotedList->each(function ($orderQuoted) use ($exclusiveUserId,$cancelType) {
            if ($orderQuoted['user_id'] == $exclusiveUserId) {
                return;
            }
            $this->cancelHelper($orderQuoted, $cancelType);
        });
    }
}
