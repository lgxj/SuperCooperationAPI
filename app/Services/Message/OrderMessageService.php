<?php


namespace App\Services\Message;


use App\Consts\ErrorCode\MessageErrorCode;
use App\Consts\GlobalConst;
use App\Consts\MessageConst;
use App\Exceptions\BusinessException;
use App\Models\Message\Order\OrderNotice;
use App\Models\Message\Order\OrderNoticeRelation;

/**
 * 订单通知
 *
 * Class OrderMessageService
 * @package App\Services\Message
 */
class OrderMessageService extends MessageService
{
    protected $counterField = 'order';

    /**
     * @param $sendUid
     * @param $toUid
     * @param $title
     * @param $content
     * @param int $type
     * @param array $extra
     * @return mixed
     * @throws BusinessException
     */
    public function sendSingleNotice($sendUid,$toUid,$title,$content,$type=0,array $extra =[]){
        if($sendUid < 0){
            throw new BusinessException('通知对像不存在',MessageErrorCode::ORDER_USER_NOT_FUND);
        }
        if($toUid <= 0){
            throw new BusinessException('被通知对像不存在',MessageErrorCode::ORDER_TO_USER_NOT_FUND);
        }
        if(empty($title)){
            throw new BusinessException('通知标题不存在',MessageErrorCode::ORDER_TITLE_EMPTY);
        }
        if(empty($content)){
            throw new BusinessException('通知内容不存在',MessageErrorCode::ORDER_CONTENT_EMPTY);
        }
        $message['user_id'] = $sendUid;
        $message['to_user_id'] = $toUid;
        $message['title'] = $title;
        $message['content'] = $content;
        $message['score'] = 0;
        $message['attach_list'] = [];
        $message['extra'] = $extra;
        $message['business_id'] = $extra['business_no'] ?? 0;
        $message['m_type'] = MessageConst::TYPE_ORDER;
        $message['m_sub_type'] = $type;
        $message['state'] = 0;
        return $this->addMessage($message);
    }

    /**
     * @param $sendUid
     * @param $toUserIds
     * @param $title
     * @param $content
     * @param int $type
     * @param array $extra
     * @return mixed
     * @throws BusinessException
     */
    public function sendBatchNotice($sendUid,array $toUserIds,$title,$content,$type=0,array $extra =[]){
        if($sendUid < 0){
            throw new BusinessException('通知对像不存在',MessageErrorCode::ORDER_USER_NOT_FUND);
        }
        if(empty($toUserIds)){
            throw new BusinessException('被通知对像不存在',MessageErrorCode::ORDER_TO_USER_NOT_FUND);
        }
        if(empty($title)){
            throw new BusinessException('通知标题不存在',MessageErrorCode::ORDER_TITLE_EMPTY);
        }
        if(empty($content)){
            throw new BusinessException('通知内容不存在',MessageErrorCode::ORDER_CONTENT_EMPTY);
        }
        $message['user_id'] = $sendUid;
        $message['to_user_id'] = $toUserIds;
        $message['title'] = $title;
        $message['content'] = $content;
        $message['score'] = 0;
        $message['attach_list'] = [];
        $message['extra'] = $extra;
        $message['business_id'] = $extra['business_no'] ?? 0;
        $message['m_type'] = MessageConst::TYPE_ORDER;
        $message['m_sub_type'] = $type;
        $message['state'] = 0;
        return $this->addMessage($message);
    }

    public function getReceiveNoticesByUserId($userId,$subType,$page=1,$pageSize = GlobalConst::PAGE_SIZE){
        $list = $this->listByPage($userId,$subType,0,$page,$pageSize);
        return $list->toArray();
    }

    public function getRidByNoticeIdAndUid($messageId, $userId)
    {
        return parent::getRidByMessageIdAndUid($messageId, $userId);
    }

    public function getRidsByNoticeIdAndUserIds($messageId, $userId)
    {
        return parent::getRidsByMessageIdAndUserIds($messageId, $userId);
    }

    public function listByPage($userId,$subType,$isOwn,$page,$pageSize = GlobalConst::PAGE_SIZE){
        return $this->getListByPage($userId,MessageConst::TYPE_ORDER,$subType,$isOwn,$page,$pageSize);
    }

    public function getMessageModel($isNew = false){
        return $isNew ? new OrderNotice() : OrderNotice::getModel();
    }

    public function getMessageRelationModel($isNew = false){
        return $isNew ? new OrderNoticeRelation() : OrderNoticeRelation::getModel();
    }

    public function deleteMsgByRid($userId, $rid, $isDeleteContent = true)
    {
        return parent::deleteMsgByRid($userId, $rid, $isDeleteContent);
    }
}
