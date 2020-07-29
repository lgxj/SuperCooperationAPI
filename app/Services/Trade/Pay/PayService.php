<?php


namespace App\Services\Trade\Pay;


use App\Bridges\User\UserBridge;
use App\Consts\Trade\OrderConst;
use App\Consts\Trade\PayConst;
use App\Consts\UserConst;
use App\Exceptions\BusinessException;
use App\Models\Trade\Order\PriceChange;
use App\Models\Trade\Pay\Pay;
use App\Services\Trade\Order\BaseTaskOrderService;
use App\Services\User\UserService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;

/**
 * 支付流水服务层
 *
 * Class PayService
 * @package App\Services\Trade\Pay
 */
class PayService extends BaseTaskOrderService
{

    /**
     * 获取任务单第一次支付成功的支付日志
     *
     * @param $orderNo
     * @return array
     */
    public function getTaskOrderMainPayLog($orderNo){
        $priceChangeModel = $this->getPriceChangeModel();
        $main = $priceChangeModel->getMainFirstPay($orderNo);
        if(empty($main)){
            return [];
        }
        return $this->getPayLogBySubBizNo($main['water_no']);
    }

    public function getAllTaskOrderPayLog($orderNo,$userId){
        return $this->getPayModel()->getAllPayLogByBizNo($orderNo,$userId);
    }

    public function getPayLog($payNo){
        $payModel = $this->getPayModel();
        $payLog = $payModel->getPayLogByPayNo($payNo);
        return $payLog ? $payLog->toArray() : [];
    }

    public function getPayLogByBizNo($bizNo, $getRefund = false, $getUser = false)
    {
        $payModel = $this->getPayModel();
        $payLog = $payModel->getAllPayLogByBizNo($bizNo)->toArray();
        $logs = [];
        $userIds = [];
        foreach ($payLog as $item) {
            $item['type'] = 'pay';
            $item['type_str'] = '支付';
            $item['amount'] = display_price($item['pay_price']);
            $item['third_fee'] = display_price($item['third_fee']);
            $item['platform_fee'] = display_price($item['platform_fee']);
            $logs[] = $item;
            $userIds[] = $item['user_id'];
        }
        if ($getRefund) {
            $refundModel = $this->getRefundModel();
            $refundLog = $refundModel->getAllRefundLogByBizNo($bizNo)->toArray();
            foreach ($refundLog as $item) {
                $item['type'] = 'refund';
                $item['amount'] = display_price($item['refund_price']);
                $item['type_str'] = '退款';
                $logs[] = $item;
                $userIds[] = $item['user_id'];
            }
        }

        if ($getUser) {
            $userService = $this->getUserService();
            $user = $userService->users($userIds);

            foreach ($logs as &$item) {
                $item['user_name'] = $user[$item['user_id']]['user_name'];
            }
        }

        usort($logs, function ($a, $b) {
            if ($a['created_at'] == $b['created_at']) return 0;
            return $a['created_at'] > $b['created_at'] ? 1 : -1;
        });

        return $logs;
    }

    public function getPayLogByPayNos(array $payNos){
        if(empty($payNos)){
            return [];
        }
        $payModel = $this->getPayModel();
        $payLogs = $payModel->getPayLogByPayNos($payNos);
        return $payLogs->toArray();
    }

    public function getPayLogByBizNosAndState(array $payNos, int $payState = PayConst::STATE_PAY){
        if(empty($payNos)){
            return [];
        }
        $payModel = $this->getPayModel();
        $payLogs = $payModel->getPayLogByBizNosAndState($payNos,$payState);
        return $payLogs->toArray();
    }

    public function getPayLogBySubBizNo($payNo){
        $payModel = $this->getPayModel();
        $payLog = $payModel->getPayLogBySubBizNo($payNo);
        return $payLog ? $payLog->toArray() : [];
    }

    public function addPayLog(array $payData){
        $payTypeList = PayConst::getPayTypeList();
        $payTypeFlipList = array_flip($payTypeList);
        $payData['pay_type']=  $payTypeFlipList[$payData['pay_type']];
        $this->deleteUnPayLogUserId($payData['user_id']);//删除大量冗余数据
        $validate = Validator::make($payData,[
            'user_id'=>'required|integer',
            'biz_no'=>'required|integer',
            'biz_source'=>'required',
            'pay_type'=>'required|integer',
            'channel'=>'required',
            'pay_no'=>'required',
            'pay_price' => 'required|integer',
            'body' => 'required'
        ],[
            'user_id.required' => '用户信息不存在',
            'biz_no.required' => '业务单号不能为空',
            'biz_source.required' => '业务来源不能为空',
            'pay_type.required'=>"支付类型不能为空",
            'channel.required'=>"支付渠道不能为空",
            'pay_price.required'=>"支付价格不能为空",
            'body.required'=>"支付名称不能为空",

        ]);
        if($validate->fails()){
            throw new BusinessException($validate->errors()->first());
        }
        $payModel = $this->getPayModel();
        $fields = $payModel->getTableColumns();
        foreach ($fields as $field) {
            if ($field == $payModel->getKeyName()) {
                continue;
            }
            if (isset($payData[$field])) {
                $payModel->$field = $payData[$field];
            }
        }
        $payModel->save();
        return $payModel->toArray();
    }


    public function deleteUnPayLogUserId($userId){
        $now = Carbon::now();
        $now->addDays(-2);//第三方支付重试次数不超过24小时
        return \App\Models\Trade\Pay\Pay::where(['user_id'=>$userId,'pay_state'=>PayConst::STATE_UN_PAY])->where('created_at','<',$now)->delete();
    }

    public function deleteUnHandleChangePriceByWaterNo($waterNo){
        $now = Carbon::now();
        $now->addMinutes(-4);
        return PriceChange::where(['water_no'=>$waterNo,'op_state'=>OrderConst::PRICE_OP_STATE_UN_HANDLE])->where('created_at','<',$now)->delete();
    }

    public function deleteUnHandleChangePriceByOrderNo($orderNo){
        $now = Carbon::now();
        $now->addDays(-2);//第三方支付重试次数不超过24小时
        return PriceChange::where(['order_no'=>$orderNo,'op_state'=>OrderConst::PRICE_OP_STATE_UN_HANDLE])->where('created_at','<',$now)->delete();
    }

    public function search($filter = [], $pageSize = 10, $orderColumn = 'created_at', $direction = 'desc')
    {
        /** @var Pay $payModel */
        $payModel = Pay::getModel();
        $userIds = [];
        $userBridge = $this->getUserBridge();
        if(!empty($filter['user_id'])){
            $userGrant = $userBridge->findByGrantType(UserConst::GRANT_LOGIN_TYPE_PHONE,$filter['user_id']);
            if($userGrant){
                $userIds[] = $userGrant['user_id'];
            }
            if (empty($userIds)) {
                $users = $userBridge->search(['user_name'=>$filter['user_id']]);
                $userIds = array_keys($users);
            }

            if(empty($userIds)){
                return new LengthAwarePaginator([],0,$pageSize);
            }
        }

        $payModel = $payModel->when(!empty($userIds), function ($query) use ($userIds) {
            $query->whereIn('user_id', $userIds);
        })->when(!empty($filter['create_time']),function ($query) use($filter){
            $query->where('created_at','>=',$filter['created_time'][0])->where('created_at','<=',$filter['created_time'][1]);
        })->when(!empty($filter['pay_no']),function ($query) use($filter){
            $query->where('pay_no',$filter['pay_no']);
        })->when(!empty($filter['biz_no']),function ($query) use($filter){
            $query->where('biz_no',$filter['biz_no']);
        })->when(!empty($filter['channel_trade_no']),function ($query) use($filter){
            $query->where('channel_trade_no',$filter['channel_trade_no']);
        })->when(isset($filter['pay_type']) && $filter['pay_type'] !== '',function ($query) use($filter){
            $query->where('pay_type',$filter['pay_type']);
        });

        $result = $payModel->orderBy($orderColumn, $direction)->paginate($pageSize);
        $tasks = collect($result->items());
        $userIds = array_unique($tasks->pluck('user_id')->toArray());
        $users = $userBridge->users($userIds);

        collect($result->items())->map(function ($item) use($users) {
            $user = $users[$item['user_id']] ?? [];
            $item['user_avatar'] = $user['user_avatar'];
            $item['user_name'] = $user['user_name'];
            $item['display_pay_type'] = PayConst::getPayTypeList($item['pay_type']);
            $item['display_channel'] = PayConst::getChannelList($item['channel']);
            $item['display_pay_state'] = PayConst::getStateList($item['pay_state']);
            $item['display_biz_source'] = PayConst::getBizSourceList($item['biz_source']);
            $item['display_pay_price'] =  display_price($item['pay_price']);
            $item['display_third_fee'] =  display_price($item['third_fee']);
            $item['display_platform_fee'] =  display_price($item['platform_fee']);
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
