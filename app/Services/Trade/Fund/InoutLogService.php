<?php


namespace App\Services\Trade\Fund;


use App\Bridges\User\UserBridge;
use App\Consts\GlobalConst;
use App\Consts\Trade\OrderConst;
use App\Consts\Trade\PayConst;
use App\Consts\UserConst;
use App\Exceptions\BusinessException;
use App\Models\Trade\Fund\InoutLog;
use App\Models\Trade\Pay\Pay;
use App\Services\Trade\Traits\ServiceTrait;
use App\Services\User\UserService;
use App\Utils\UniqueNo;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 收支流水记录
 *
 * Class InoutLogService
 * @package App\Services\Trade\Fund
 */
class InoutLogService
{

    use ServiceTrait;

    /**
     * 添加收支记录
     *
     * @param int $userId
     * @param int $payMoney 支出表示负数，收入表示正数
     * @param string $channel
     * @param int $intOutType
     * @param int $bizSource
     * @param string $bizNo 本身是bigint PHP本身int放不下19位
     * @param string $fromTo
     * @param string $relationWaterNo 本身是bigint PHP本身int放不下18位
     * @return array
     * @throws BusinessException
     */
    public function addInoutLog(int $userId,int $payMoney,string $channel,int $intOutType,int $bizSource,$bizNo,string $fromTo = '0',string $relationWaterNo = ''){
        if($userId <= 0){
            throw new BusinessException("用户ID不存在");
        }
        $inoutTypeList = array_keys(PayConst::getInoutTypeList());
        $sourceTypeList = array_keys(PayConst::getBizSourceList());
        $channels = array_keys(PayConst::getChannelList());
        if(!in_array($intOutType,$inoutTypeList)){
            throw new BusinessException("流水出入类型错误");
        }
        if(!in_array($bizSource,$sourceTypeList)){
            throw new BusinessException("业务类型错误");
        }
        if(!in_array($channel,$channels)){
            throw new BusinessException("渠道错误");
        }
        if(empty($bizNo)){
            throw new BusinessException("业务单号错误");
        }

        $accountService = $this->getAccountService();
        $account = $accountService->getAccountByUserId($userId);
        $inoutLog = new InoutLog();
        $inoutLog->user_id = $userId;
        $inoutLog->water_no = UniqueNo::buildInoutNo($userId,$intOutType);
        $inoutLog->available_balance = $account['available_balance'] ?? 0;
        $inoutLog->money = $payMoney;
        $inoutLog->in_out = $payMoney < 0 ? PayConst::IN_OUT_OUT : PayConst::IN_OUT_IN;
        $inoutLog->in_out_type = $intOutType;
        $inoutLog->biz_source = $bizSource;
        $inoutLog->biz_no = $bizNo;
        $inoutLog->channel = $channel;
        $inoutLog->from_to = $fromTo;
        $inoutLog->relation_water_no = $relationWaterNo ?: 0;
        $inoutLog->save();
        return $inoutLog->toArray();
    }


    public function getListByMonth($userId,$timePeriod,$page=1,$pageSize = GlobalConst::PAGE_SIZE,$lastId = null){
        $carbonTime = Carbon::parse($timePeriod);
        $start = Carbon::parse($timePeriod)->firstOfMonth();
        $end = $carbonTime->endOfMonth();
        $model = InoutLog::where(['user_id' => $userId])->whereBetween('created_at', [$start, $end])->select('inout_id')->orderByDesc('inout_id');
        if(!is_null($lastId)){
            $model->forPageBeforeId($pageSize,$lastId,'inout_id');
        }else {
            $model->forPage($page,$pageSize);
        }
        $inoutLogIds = $model->pluck('inout_id');//延迟查讯，解决慢查、file sort
        if(empty($inoutLogIds)){
            return [];
        }
        $out = InoutLog::where(['user_id' => $userId,'in_out'=>PayConst::IN_OUT_OUT])->whereBetween('created_at', [$start, $end])->sum('money');
        $in = InoutLog::where(['user_id' => $userId,'in_out'=>PayConst::IN_OUT_IN])->whereBetween('created_at', [$start, $end])->sum('money');
        $logs = InoutLog::whereIn('inout_id',$inoutLogIds)->get()->keyBy('inout_id')->toArray();
        $detailOrderService = $this->getDetailService();

        $orderNos = collect($logs)->map(function ($log){
            if($log['biz_source'] == PayConst::SOURCE_TASK_ORDER){
               return $log['biz_no'];
            }
        })->toArray();
        $orders = $detailOrderService->getOrders($orderNos,'',false,true);
        $list = [];
        foreach ($inoutLogIds as $inoutLogId){
            $log = $logs[$inoutLogId] ?? [];
            $tmp['created_at'] = $log['created_at'];
            $tmp['inout_id'] = $log['inout_id'];
            $tmp['user_id'] = $log['user_id'];
            $tmp['in_out'] = $log['in_out'];
            $tmp['in_out_type'] = $log['in_out_type'];
            $tmp['biz_source'] = $log['biz_source'];
            $tmp['biz_no'] = $log['biz_no'];
            $tmp['channel'] = $log['channel'];
            $tmp['money'] = $log['money'];
            $tmp['display_money'] = display_price($log['money']);
            $tmp['available_balance'] = $log['available_balance'];
            $tmp['display_available_balance'] = display_price($log['available_balance']);
            $tmp['in_out_type_desc'] = PayConst::getInoutTypeList($log['in_out_type']);
            $tmp['in_out_type_logo'] = PayConst::getInoutTypeLogo($log['in_out_type']);
            $tmp['channel_desc'] = PayConst::getChannelList($log['channel']);
            $tmp['third_fee'] =  0;
            $tmp['platform_fee'] =  0;
            $tmp['platform_income'] = 0;
            $tmp['service_price'] = 0;
            if($log['biz_source'] == PayConst::SOURCE_TASK_ORDER){
                $order = $orders[$log['biz_no']] ?? [];
                $compensateService = $this->getCompensateService();
                $compensatePrice = 0;
                $platIncome = 0;
                if($log['in_out_type'] == PayConst::INOUT_HELPER_COMPLETE) {//帮手完成任务后，有逾期赔付
                    $compensate = $compensateService->getUserCompensateByUserId($log['biz_no'], $log['user_id'], PayConst::INOUT_OVERTIME_COMPENSATE);
                    $compensatePrice = $compensate['compensate_price'] ?? 0;
                    $compensatePrice = abs($compensatePrice);
                    if($order['order_type'] == OrderConst::TYPE_COMPETITION) {
                        $receiver = $detailOrderService->getValidReceiverByOrderNo($order['order_no'],$userId);
                        $realPrice = $receiver ? $receiver['quoted_price'] : $order['origin_price'];
                        $platIncome = bcsub($realPrice, $compensatePrice);
                    }else{
                        $platIncome = bcsub($order['origin_price'], $compensatePrice);
                    }
                    $platIncome = bcsub($platIncome , $log['money']);

                }elseif($log['in_out_type'] == PayConst::INOUT_TASK_REFUND){//雇主取消任务，有可能赔付帮手
                    $compensate = $compensateService->getUserCompensateByUserId($log['biz_no'], $log['user_id'], PayConst::INOUT_EMPLOYER_COMPENSATE_COMPLETE);
                    $compensatePrice = $compensate['compensate_price'] ?? 0;
                    $compensatePrice = abs($compensatePrice);
                    $platIncome =  bcadd($log['money'],$compensatePrice);
                    $computeMoney = $log['money'];
                    $servicesPrice = array_sum($order['services']);
                    if($log['money'] > $order['origin_price']){
                        $computeMoney  = bcadd($order['origin_price'],$servicesPrice);//任务未接单之前，服务费退还
                    }
                    $platIncome = bcsub($computeMoney , $platIncome);
                    $tmp['service_price'] = display_price($servicesPrice);

                }
                //$tmp['in_out_type_desc'] = $tmp['in_out_type_desc'] . ' ('.mb_substr($order['order_name'],0,10).')';
                $tmp['platform_income'] = display_price($platIncome);
            }
            $list[] = $tmp;
        }
        return ['list'=>$list,'out'=>display_price($out),'in'=>display_price($in)];
    }

    public function getTodayIncome($userId){
        $endDay = Carbon::now()->endOfDay();
        $startDay = Carbon::now()->startOfDay();
        $sum =  InoutLog::where(['user_id' => $userId,'in_out'=>PayConst::IN_OUT_IN])->whereBetween('created_at', [$startDay, $endDay])->sum('money');
        return $sum ? $sum : 0;
    }


    public function search($filter = [], $pageSize = 10, $orderColumn = 'created_at', $direction = 'desc'){
        /** @var Pay $inoutLogModel */
        $inoutLogModel = InoutLog::getModel();
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

        $inoutLogModel = $inoutLogModel->when(!empty($userIds), function ($query) use ($userIds) {
            $query->whereIn('user_id', $userIds);
        })->when(!empty($filter['create_time']),function ($query) use($filter){
            $query->where('created_at','>=',$filter['created_time'][0])->where('created_at','<=',$filter['created_time'][1]);
        })->when(!empty($filter['water_no']),function ($query) use($filter){
            $query->where('water_no',$filter['water_no']);
        })->when(!empty($filter['biz_no']),function ($query) use($filter){
            $query->where('biz_no',$filter['biz_no']);
        })->when(isset($filter['in_out_type']) && $filter['in_out_type'] !== '',function ($query) use($filter){
            $query->where('in_out_type',$filter['in_out_type']);
        });

        $result = $inoutLogModel->orderBy($orderColumn, $direction)->paginate($pageSize);
        $tasks = collect($result->items());
        $userIds = array_unique($tasks->pluck('user_id')->toArray());
        $users = $userBridge->users($userIds);

        collect($result->items())->map(function ($item) use($users) {
            $user = $users[$item['user_id']] ?? [];
            $item['user_avatar'] = $user['user_avatar'];
            $item['user_name'] = $user['user_name'];
            $item['display_in_out_type'] = PayConst::getInoutTypeList($item['in_out_type']);
            $item['display_channel'] = PayConst::getChannelList($item['channel']);
            $item['display_biz_source'] = PayConst::getBizSourceList($item['biz_source']);
            $item['display_money'] =  display_price($item['money']);
            $item['display_available_balance'] =  display_price($item['available_balance']);
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
