<?php


namespace App\Services\Message;


use App\Consts\ErrorCode\MessageErrorCode;
use App\Consts\GlobalConst;
use App\Consts\MessageConst;
use App\Exceptions\BusinessException;
use App\Models\Message\Comment\Comment;
use App\Models\Message\Comment\CommentRelation;
use App\Services\Message\Entity\CommentEntity;

/**
 * 帮手与雇主互评服务
 *
 * Class CommentMessageService
 * @package App\Services\Message
 */
class CommentMessageService extends MessageService
{

    /**
     * @param CommentEntity $commentEntity
     * @return mixed
     * @throws BusinessException
     */
    public function addComment(CommentEntity $commentEntity){
        if($commentEntity->userId <= 0){
            throw new BusinessException('评论对像不存在',MessageErrorCode::COMMENT_USER_NOT_FUND);
        }
        if($commentEntity->toUserId <= 0){
            throw new BusinessException('被评论对像不存在',MessageErrorCode::COMMENT_TO_USER_NOT_FUND);
        }
        if(empty($commentEntity->title) && empty($commentEntity->content)){
            throw new BusinessException('评论内容不存在',MessageErrorCode::COMMENT_CONTENT_EMPTY);
        }
        $message['user_id'] = $commentEntity->userId;
        $message['to_user_id'] = $commentEntity->toUserId;
        $message['title'] = $commentEntity->title;
        $message['content'] = $commentEntity->content;
        $message['score'] = $commentEntity->score;
        $message['attach_list'] = $commentEntity->attachmentList;
        $message['extra'] = $commentEntity->extra;
        $message['business_id'] = $commentEntity->businessId;
        $message['m_type'] = MessageConst::TYPE_COMMENT;
        $message['m_sub_type'] = $commentEntity->subType;
        $message['state'] = $commentEntity->state;
        return $this->addMessage($message);
    }

    public function avgUserLatestCommentScore($userId, $subType,$limit = 50){
        if($userId <= 0){
            return 0;
        }
        $avg =  parent::avgUserLatestScore($userId,MessageConst::TYPE_COMMENT,$subType,$limit);
        return round($avg,1);
    }

    public function getReceiveCommentsByUserId($userId,$isOwn,$subType,$page=1,$pageSize = GlobalConst::PAGE_SIZE){
        $list = $this->listByPage($userId,$subType,$isOwn,$page,$pageSize);
        return $list->toArray();
    }

    public function getCommentsByBusinessId($businessId,$subType = null,$isOwn = null,$page = 1,$pageSize = GlobalConst::PAGE_SIZE){
        return $this->getMessagesByBusinessId($businessId,MessageConst::TYPE_COMMENT,$subType,$isOwn,$page,$pageSize);
    }

    public function listByPage($userId,$subType,$isOwn,$page,$pageSize = GlobalConst::PAGE_SIZE){
        return $this->getListByPage($userId,MessageConst::TYPE_COMMENT,$subType,$isOwn,$page,$pageSize);
    }

    public function getMessageModel($isNew = false){
        return $isNew ? new Comment() : Comment::getModel();
    }

    public function getMessageRelationModel($isNew = false){
        return $isNew ? new CommentRelation() : CommentRelation::getModel();
    }
}
