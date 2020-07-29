<?php


namespace App\Services\Trade\Order;


use App\Consts\DBConnection;
use App\Consts\MessageConst;
use App\Consts\Trade\OrderConst;
use App\Exceptions\BusinessException;
use App\Models\User\User;
use App\Services\Trade\Traits\ModelTrait;
use App\Services\Trade\Traits\ServiceTrait;
use App\Services\User\AcceptPushService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 附近任务推送
 *
 * Class TaskNoticeService
 * @package App\Services\Trade\Order
 */
class TaskNoticeService
{
    use ServiceTrait;

    use ModelTrait;

    /**
     * @param $orderNo
     * @return bool
     * @throws BusinessException
     */
    public function pushTaskToHelper($orderNo){
        $taskModel = $this->getTaskOrderModel();
        $task = $taskModel->getByOrderNo($orderNo);
        if(empty($task)){
            return false;
        }
        if(!in_array($task['order_state'],[OrderConst::EMPLOYER_STATE_UN_RECEIVE,OrderConst::EMPLOYER_STATE_UN_CONFIRM])){
            return false;
        }
        $addressList = $this->getDetailService()->getAddressByOrderNos([$orderNo]);
        $address = $addressList[$orderNo][0] ?? [];
        if(empty($address)){
            return false;
        }
        $employerUserId = $task['user_id'];
        $helperLevel = $task['helper_level'];
        $employer = $this->getUserService()->user($employerUserId);
        if(empty($employer)){
            return false;
        }
        $sendCounter = 25;
        $acceptPushBridge = $this->getAcceptUserBridge();
        list($users,$total) = $acceptPushBridge->getNearByUsersWithYunTu($address['lng'],$address['lat'],1);
        if(empty($users)){
            return false;
        }
        $userIds = array_keys($users);
        $userIds = $acceptPushBridge->filterUser($userIds,$helperLevel);
        $userIds = $acceptPushBridge->filterBlackList($employerUserId,$userIds);
        if(count($userIds) < $sendCounter && $total > $sendCounter){
            list($twoUsers,$total) = $acceptPushBridge->getNearByUsersWithYunTu($address['lng'],$address['lat'],2);
            $towUserIds = array_keys($twoUsers);
            $towUserIds = $acceptPushBridge->filterUser($towUserIds,$helperLevel);
            $towUserIds = $acceptPushBridge->filterBlackList($employerUserId,$towUserIds);
            foreach ($towUserIds as $towUserId){
                if(!in_array($towUserId,$userIds)){
                    $userIds[] = $towUserId;
                }
            }
        }
        $pos = array_search($employerUserId,$userIds);
        if(isset($userIds[$pos])){
            unset($userIds[$pos]);
        }
        $count = count($userIds);
        if($count <= 0){
           return false;
        }
        try {
            $toUserIds = array_slice($userIds,0,$count > $sendCounter ? $sendCounter : $count);print_r($toUserIds);
            if($toUserIds) {
                list_order_send_message($toUserIds, MessageConst::TYPE_ORDER_HELPER_NEW, $orderNo);
            }
        } catch (BusinessException $e) {
            Log::error("new order push nearby orderNo:{$orderNo} message:{$e->getMessage()}");
        }
    }




    protected function getAcceptUserBridge(){
        return new AcceptPushService();
    }

    /**
     * 根据配置条件过滤用户，前期用户不多，暂时不采取这种方法
     *
     * @param $task
     * @param $employer
     * @param $address
     * @return array
     */
    protected function getRandUserByConfig($task,$employer,$address){
        $helperLevel = $task['helper_level'];
        $employerLevel = $employer['employer_level'];
        $category = $task['category'];
        $payPrice = $task['pay_price'];
        $maxId = 0;
        $hour = date('H',strtotime($task['start_time']));
        $hour = $hour * 100;
        $limit = 1000;
        $distance = 500;//最小距离500
        $sendUserCount = 20;
        $employerUserId = $task['user_id'];
        $userDistanceIds = $this->filter($employerUserId,$employerLevel,$helperLevel,$distance,$category,$payPrice,$hour,$address['lng'],$address['lat'],$maxId,$limit);//先随机选出有距离的一批/精准推荐
        if(count($userDistanceIds) <= $sendUserCount ){
            $userDistanceIds2 = $this->filter($employerUserId,0,$helperLevel,0,$category,0,0,$address['lng'],$address['lat'],$maxId,200);//对距离不设限/随机推荐
            foreach ($userDistanceIds2 as $userId=>$distance){
                if(!isset($userDistanceIds[$userId])){
                    $userDistanceIds[$userId] = $distance;
                }
            }
        }
        asort($userDistanceIds);//按距离顺序
        $sendDistanceList = [];
        $i = 0;
        //优先发送最近距离的
        foreach ($userDistanceIds  as $userId=>$distance){
            if($distance > 0){
                $sendDistanceList[$userId] = $distance;
                $i++;
            }
            if($i > $sendUserCount){
                break;
            }
        }
        //最近距离的不足指定数量，从不设限的距离补满
        foreach ($userDistanceIds as  $userId=>$distance){
            if($i >= $sendUserCount){
                break;
            }
            if(!isset($sendDistanceList[$userId])){
                $sendDistanceList[$userId] = $distance;
            }
        }
        unset($sendDistanceList[$employerUserId]);
        if(empty($sendDistanceList)){
            return [];
        }
        $toUserIds = array_keys($sendDistanceList);
        return $toUserIds;
    }

    /**
     * 根据配置条件过滤用户，前期用户不多，暂时不采取这种方法
     * @param $employerUserId
     * @param $employerLevel
     * @param $helperLevel
     * @param $distance
     * @param $taskCategory
     * @param $payPrice
     * @param $hour
     * @param $lng
     * @param $lat
     * @param int $maxId
     * @param int $limit
     * @return array
     */
    protected function filter($employerUserId,$employerLevel,$helperLevel,$distance,$taskCategory,$payPrice,$hour,$lng,$lat,&$maxId = 0,$limit =1000){
        $acceptPushBridge = $this->getAcceptUserBridge();
        $userIds = $acceptPushBridge->searchConfig($payPrice,$employerLevel,$distance,$hour,$limit,$maxId);
        $userIds = $acceptPushBridge->filterCategory($userIds,$taskCategory);//过滤分类
        $userIds = $acceptPushBridge->filterBlackList($employerUserId,$userIds); //过滤黑名单
        $userIds = $acceptPushBridge->filterUser($userIds,$helperLevel);//过滤不符合任务等级和不合规用户
        $userDistanceIds = $acceptPushBridge->filterDistance($userIds,$lng,$lat,!empty($distance));//过虑用户距离
        return $userDistanceIds;
    }
}
