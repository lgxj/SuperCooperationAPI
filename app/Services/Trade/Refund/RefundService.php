<?php


namespace App\Services\Trade\Refund;


use App\Bridges\User\UserBridge;
use App\Consts\Trade\PayConst;
use App\Consts\Trade\RefundConst;
use App\Consts\UserConst;
use App\Exceptions\BusinessException;
use App\Models\Trade\Pay\PayRefund;
use App\Services\ScService;
use App\Services\Trade\Traits\ModelTrait;
use App\Services\Trade\Traits\ServiceTrait;
use App\Services\User\UserService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;

/**
 * 退款流水服务层
 *
 * Class RefundService
 * @package App\Services\Trade\Refund
 */
class RefundService extends ScService
{
    use ServiceTrait;
    use ModelTrait;

    /**
     * @param array $refundData
     * @param $state
     * @return array
     * @throws BusinessException
     */
    public function addRefund(array $refundData,$state){

        $validate = Validator::make($refundData,[
            'user_id'=>'required|integer',
            'biz_no'=>'required|integer',
            'biz_source'=>'required|integer',
            'refund_no'=>'required',
            'channel'=>'required',
            'pay_no'=>'required',
            'refund_price' => 'required|integer',
            'channel_refund_no' => 'required'
        ],[
            'user_id.required' => '用户信息不存在',
            'biz_no.required' => '业务单号不能为空',
            'biz_source.required' => '业务来源不能为空',
            'refund_no.required'=>"退款单号不能为空",
            'channel.required'=>"支付渠道不能为空",
            'channel_refund_no.required'=>"第三方退款单号不能为空",
            'refund_price.required'=>"退款金额不能为空",

        ]);
        if($validate->fails()){
            throw new BusinessException($validate->errors()->first());
        }
        $refundModel = $this->getRefundModel();
        $fields = $refundModel->getTableColumns();
        foreach ($fields as $field) {
            if ($field == $refundModel->getKeyName()) {
                continue;
            }
            if (isset($refundData[$field])) {
                $refundModel->$field = $refundData[$field];
            }
        }
        $refundModel->state = $state;
        $refundModel->save();
        return $refundModel->toArray();
    }


    public function sumRefundByOrderNo($orderNo,$userId){
        return $this->getRefundModel()->where(['biz_no'=>$orderNo,'user_id'=>$userId,'state'=>1])->whereIn('refund_type',PayConst::getEmployerRefundType())->sum('refund_price');
    }

    public function search($filter = [], $pageSize = 10, $orderColumn = 'created_at', $direction = 'desc')
    {
        /** @var PayRefund $payRefundModel */
        $payRefundModel = PayRefund::getModel();
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

        $payRefundModel = $payRefundModel->when(!empty($userIds), function ($query) use ($userIds) {
            $query->whereIn('user_id', $userIds);
        })->when(!empty($filter['create_time']),function ($query) use($filter){
            $query->where('created_at','>=',$filter['created_time'][0])->where('created_at','<=',$filter['created_time'][1]);
        })->when(!empty($filter['pay_no']),function ($query) use($filter){
            $query->where('pay_no',$filter['pay_no']);
        })->when(!empty($filter['biz_no']),function ($query) use($filter){
            $query->where('biz_no',$filter['biz_no']);
        })->when(!empty($filter['refund_no']),function ($query) use($filter){
            $query->where('refund_no',$filter['refund_no']);
        })->when(!empty($filter['channel_refund_no']),function ($query) use($filter){
            $query->where('channel_refund_no',$filter['channel_refund_no']);
        })->when(isset($filter['refund_type']) && $filter['refund_type'] !== '',function ($query) use($filter){
            $query->where('refund_type',$filter['refund_type']);
        });

        $result = $payRefundModel->orderBy($orderColumn, $direction)->paginate($pageSize);
        $tasks = collect($result->items());
        $userIds = array_unique($tasks->pluck('user_id')->toArray());
        $users = $userBridge->users($userIds);

        collect($result->items())->map(function ($item) use($users) {
            $user = $users[$item['user_id']] ?? [];
            $item['user_avatar'] = $user['user_avatar'];
            $item['user_name'] = $user['user_name'];
            $item['display_refund_type'] = PayConst::getInoutTypeList($item['refund_type']);
            $item['display_channel'] = PayConst::getChannelList($item['channel']);
            $item['display_state'] = PayConst::getRefundStateList($item['state']);
            $item['display_biz_source'] = PayConst::getBizSourceList($item['biz_source']);
            $item['display_refund_price'] =  display_price($item['refund_price']);
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
