<?php


namespace App\Services\Message;


use App\Consts\ErrorCode\MessageErrorCode;
use App\Consts\GlobalConst;
use App\Consts\MessageConst;
use App\Exceptions\BusinessException;
use App\Models\Message\Notice\Notice;
use App\Models\Message\Notice\NoticeRelation;

/**
 * 系统通知
 *
 * Class NoticeMessageService
 * @package App\Services\Message
 */
class NoticeMessageService extends MessageService
{
    protected $counterField = 'notice';

    /**
     * @param $sendUid
     * @param $toUid
     * @param $title
     * @param $content
     * @param int $type
     * @param string $href
     * @return mixed
     * @throws BusinessException
     */
    public function sendNotice($sendUid,$toUid,$title,$content,$type=0,$href = ''){
        if($sendUid < 0){
            throw new BusinessException('通知对像不存在',MessageErrorCode::NOTICE_USER_NOT_FUND);
        }
        if($toUid <= 0){
            throw new BusinessException('被通知对像不存在',MessageErrorCode::NOTICE_TO_USER_NOT_FUND);
        }
        if(empty($title)){
            throw new BusinessException('通知标题不存在',MessageErrorCode::NOTICE_TITLE_EMPTY);
        }
        if(empty($content)){
            throw new BusinessException('通知内容不存在',MessageErrorCode::NOTICE_CONTENT_EMPTY);
        }
        $message['user_id'] = $sendUid;
        $message['to_user_id'] = $toUid;
        $message['title'] = $title;
        $message['content'] = $content;
        $message['score'] = 0;
        $message['attach_list'] = [];
        $message['extra'] = ['href'=>$href];
        $message['business_id'] = 0;
        $message['m_type'] = MessageConst::TYPE_NOTICE;
        $message['m_sub_type'] = $type;
        $message['state'] = 0;
        return $this->addMessage($message);
    }

    public function getReceiveNoticesByUserId($userId,$subType,$page=1,$pageSize = GlobalConst::PAGE_SIZE){
        $list = $this->listByPage($userId,$subType,0,$page,$pageSize);
        return $list->toArray();
    }

    public function deleteMsgByRid($userId, $rid, $isDeleteContent = true)
    {
        return parent::deleteMsgByRid($userId, $rid, $isDeleteContent);
    }

    public function listByPage($userId,$subType,$isOwn,$page,$pageSize = GlobalConst::PAGE_SIZE){
        return $this->getListByPage($userId,MessageConst::TYPE_NOTICE,$subType,$isOwn,$page,$pageSize);
    }

    public function getMessageModel($isNew = false){
        return $isNew ? new Notice() : Notice::getModel();
    }

    public function getMessageRelationModel($isNew = false){
        return $isNew ? new NoticeRelation() : NoticeRelation::getModel();
    }
}
