<?php


namespace App\Services\Trade\Traits;


use App\Consts\ErrorCode\TaskOrderErrorCode;
use App\Consts\Trade\OrderConst;
use App\Exceptions\BusinessException;
use App\Models\Trade\Entity\TaskOrderEntity;
use App\Models\Trade\Order\TypeChange;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

trait OrderEntityCheckTrait
{
    use ServiceTrait;

    /**
     * @param TaskOrderEntity $taskOrderEntity
     * @throws BusinessException
     */
    protected function checkGeneral(TaskOrderEntity $taskOrderEntity){
        if($taskOrderEntity->category <= 0){
            throw new BusinessException("请选择任务分类",TaskOrderErrorCode::SAVE_PARAM_CHECK_CATEGORY);
        }
        if($taskOrderEntity->originPrice <= 0){
            throw new BusinessException("请输入服务费",TaskOrderErrorCode::SAVE_PARAM_CHECK_MONEY);
        }
        if(empty($taskOrderEntity->addressList)){
            throw new BusinessException("请选择服务地址",TaskOrderErrorCode::SAVE_PARAM_CHECK_ADDRESS);
        }
        if(count($taskOrderEntity->addressList) > 2){
            throw new BusinessException("服务地址不能超过2个",TaskOrderErrorCode::SAVE_PARAM_CHECK_ADDRESS_LIMIT);
        }
        $typeList = OrderConst::getTypeList();
        if(!in_array($taskOrderEntity->orderType,array_keys($typeList))){
            throw new BusinessException("订单类型错误",TaskOrderErrorCode::SAVE_PARAM_CHECK_TYPE);
        }
        if(!trim($taskOrderEntity->orderName)){
            throw new BusinessException("任务名称不能为空",TaskOrderErrorCode::SAVE_PARAM_CHECK_NAME);
        }
        if(mb_strlen($taskOrderEntity->orderName) > 15){
            throw new BusinessException("任务名称字数限制在15字以内",TaskOrderErrorCode::SAVE_PARAM_CHECK_NAME_LENGTH);
        }
        if($taskOrderEntity->userId <= 0){
            throw new BusinessException("用户登录凭证不存在",TaskOrderErrorCode::SAVE_PARAM_CHECK_USER);
        }
        $attachmentCount = count($taskOrderEntity->attachmentList);
        if($taskOrderEntity->attachmentType == OrderConst::ATTACHMENT_TYPE_FILE && $attachmentCount > 3){
            throw new BusinessException("图片不能超过3张",TaskOrderErrorCode::SAVE_PARAM_CHECK_PIC);
        }
        if($taskOrderEntity->attachmentType == OrderConst::ATTACHMENT_TYPE_VIDEO && $attachmentCount > 1){
            throw new BusinessException("视频不能超过1个",TaskOrderErrorCode::SAVE_PARAM_CHECK_VIDEO);
        }
        $userService = $this->getUserService();
        $users = $userService->users([$taskOrderEntity->userId]);
        $user = $users[$taskOrderEntity->userId] ?? [];
        if(!$user){
            throw new BusinessException('用户不存在',TaskOrderErrorCode::SAVE_PARAM_CHECK_USER);
        }

    }

    /**
     * @param TaskOrderEntity $taskOrderEntity
     * @throws BusinessException
     */
    protected function checkOrderText(TaskOrderEntity $taskOrderEntity){
        if(empty($taskOrderEntity->voiceUrl) && empty($taskOrderEntity->voiceText)){
            throw new BusinessException("任务描述不能为空",TaskOrderErrorCode::SAVE_PARAM_CHECK_CONTENT);
        }
        if($taskOrderEntity->userId <= 0){
            throw new BusinessException("用户登录凭证不存在",TaskOrderErrorCode::SAVE_PARAM_CHECK_USER);
        }
        if($taskOrderEntity->orderNo <= 0){
            throw new BusinessException("任务单号不存在",TaskOrderErrorCode::SAVE_PARAM_CHECK_NO);
        }
    }

    /**
     * @param TaskOrderEntity $taskOrderEntity
     * @throws BusinessException
     */
    protected function checkTime(TaskOrderEntity $taskOrderEntity){
        if(!trim($taskOrderEntity->startTime)){
            throw new BusinessException("任务开始时间不能为空",TaskOrderErrorCode::SAVE_PARAM_CHECK_TIME);
        }
        if(!trim($taskOrderEntity->endTime)){
            throw new BusinessException("任务结束时间不能为空",TaskOrderErrorCode::SAVE_PARAM_CHECK_TIME);
        }
        $configBridge = $this->getGlobalConfigBridge();
        $configs = $configBridge->getByKeys(['task_current_time_diff','task_start_end_diff']);
        $taskCurrentTimeDiff = $configs['task_current_time_diff'] ?? 30;
        $taskStartEndDiff = $configs['task_start_end_diff'] ?? 60;
        $taskCurrentTimeDiff = $taskCurrentTimeDiff - 2;
        $taskStartEndDiff = $taskStartEndDiff - 2;
        $startTime = Carbon::parse($taskOrderEntity->startTime);
        $endTime = Carbon::parse($taskOrderEntity->endTime);
        $nowTime = Carbon::now();
        if($startTime->isAfter($endTime)){
            throw new BusinessException("开始时间必须小于结束时间",TaskOrderErrorCode::SAVE_PARAM_CHECK_TIME);
        }
        if($nowTime->addMinutes($taskCurrentTimeDiff)->isAfter($startTime)){
            $taskCurrentTimeDiff = $taskCurrentTimeDiff+2;
            throw new BusinessException("开始时间必须在当前时间{$taskCurrentTimeDiff}分钟之后",TaskOrderErrorCode::SAVE_PARAM_CHECK_TIME);
        }
        $diffMinutes = $startTime->diffInMinutes($endTime);
        if($diffMinutes < $taskStartEndDiff){
            $taskStartEndDiff = $taskStartEndDiff + 2;
            throw new BusinessException("开始时间-结束时间必须相差{$taskStartEndDiff}分钟",TaskOrderErrorCode::SAVE_PARAM_CHECK_TIME);
        }

    }

    protected function checkPrice(TaskOrderEntity $taskOrderEntity){
        $minPrice = OrderConst::TYPE_GENERAL_LOW_PRICE;
        if($taskOrderEntity->orderType == OrderConst::TYPE_COMPETITION){
            $minPrice = OrderConst::TYPE_COMPETITION_LOW_PRICE;
        }
        $displayMixPrice = db_price($minPrice);
        if($taskOrderEntity->originPrice < $displayMixPrice){
            throw new BusinessException("服务价格必须{$minPrice}元以上",TaskOrderErrorCode::SAVE_PARAM_CHECK_SERVICE);
        }

    }

    /**
     * @param Model $taskOrder
     * @param TaskOrderEntity $taskOrderEntity
     * @throws BusinessException
     */
    protected function checkOriginPrice(Model $taskOrder, TaskOrderEntity $taskOrderEntity){
        if($taskOrder->order_state > OrderConst::EMPLOYER_STATE_UN_START ){
            if($taskOrder->origin_price > $taskOrderEntity->originPrice) {
                throw new BusinessException("已支付的服务费只能加价，不能减价",TaskOrderErrorCode::SAVE_PARAM_CHECK_SERVICE_NO_CHANGE);
            }
        }else{
            if($taskOrderEntity->originPrice <= 0){
                throw new BusinessException("请填写服务费",TaskOrderErrorCode::SAVE_PARAM_CHECK_MONEY);
            }
        }
    }

    /**
     * @param Model $taskOrder
     * @param TaskOrderEntity $taskOrderEntity
     * @throws BusinessException
     */
    protected function checkOrderServiceChange(Model $taskOrder, TaskOrderEntity $taskOrderEntity)
    {
        $detailService = $this->getDetailService();
        $enableServices = $detailService->getEnableServiceByOrderNo($taskOrderEntity->orderNo);
        $urgePrice = $enableServices[OrderConst::SERVICE_PRICE_TYPE_URGE] ?? 0;
        $insurancePrice = $enableServices[OrderConst::SERVICE_PRICE_TYPE_INSURANCE] ?? 0;
        $facePrice = $enableServices[OrderConst::SERVICE_PRICE_TYPE_FACE] ?? 0;
        if ($taskOrder->order_state == OrderConst::EMPLOYER_STATE_UN_START) {
            return;
        }
        if($taskOrderEntity->urgentPrice < $urgePrice ){
            throw new BusinessException("加急费用已支付，不能取消",TaskOrderErrorCode::SAVE_PARAM_CHECK_SERVICE_NO_CHANGE_URGE);
        }
        if($taskOrderEntity->insurancePrice < $insurancePrice ){
            throw new BusinessException("保险费用已支付，不能取消",TaskOrderErrorCode::SAVE_PARAM_CHECK_SERVICE_NO_CHANGE_INSURANCE);
        }
        if($taskOrderEntity->facePrice < $facePrice ){
            throw new BusinessException("刷险接单费用已支付，不能取消",TaskOrderErrorCode::SAVE_PARAM_CHECK_SERVICE_NO_CHANGE_FACE);
        }

    }

    /**
     * @param Model $taskOrder
     * @param TaskOrderEntity $taskOrderEntity
     * @throws BusinessException
     */
    protected function checkOrderType(Model $taskOrder, TaskOrderEntity $taskOrderEntity)
    {
        $typeChange = TypeChange::where('order_no',$taskOrderEntity->orderNo)->first();
        if($taskOrder->order_type != $taskOrderEntity->orderType && $typeChange){
            $orderTypeDesc = OrderConst::getTypeList($taskOrderEntity->orderType);
            throw new BusinessException("您之前是{$orderTypeDesc}任务单，不能再次转换",TaskOrderErrorCode::SAVE_PARAM_CHECK_TYPE_CHANGE);
        }
        if($taskOrder->order_type != $taskOrderEntity->orderType && $taskOrderEntity->orderType == OrderConst::TYPE_COMPETITION){
            if($taskOrderEntity->originPrice < $taskOrder->origin_price){
                throw new BusinessException("竞价订单改为悬赏订单，服务价格不能低于之前的竞价订单价格",TaskOrderErrorCode::SAVE_PARAM_CHECK_TYPE_CHANGE_PRICE);//不发生退款操作，悬赏订单是可输入的
            }
        }
    }
}
