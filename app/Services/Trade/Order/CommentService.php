<?php


namespace App\Services\Trade\Order;


use App\Bridges\Message\CommentMessageBridge;
use App\Bridges\User\LabelBridge;
use App\Consts\MessageConst;
use App\Consts\Trade\OrderConst;
use App\Consts\UserConst;
use App\Exceptions\BusinessException;
use App\Services\Message\CommentMessageService;
use App\Services\Message\Entity\CommentEntity;
use App\Services\Trade\Traits\ModelTrait;
use App\Services\Trade\Traits\ServiceTrait;
use App\Services\User\LabelService;
use Illuminate\Database\Eloquent\Model;

/**
 * 评论服务层
 *
 * Class CommentService
 * @package App\Services\Trade\Order
 */
class CommentService
{
    use ServiceTrait;
    use ModelTrait;

    /**
     * 评价雇主
     *
     * @param $orderNo
     * @param $content
     * @param $star
     * @param array $labels
     * @param array $attachList
     * @throws BusinessException
     * @return int
     */
    public function commentEmployer($orderNo,$userId,$content,$star,array $labels = [],array $attachList = []){
        $task = $this->check($orderNo,$star,$content,$labels,$attachList);
        if($task['comment_id'] > 0){
            throw new BusinessException('您已评价，不用再评价');
        }
        if($task['helper_user_id'] != $userId){
            throw new BusinessException('您不能评价其它人的任务');
        }
        $commentBridge = $this->getCommentBridge();
        $labelBridge = $this->getLabelBridge();
        $labels = $labelBridge->saveUserLabel($task['user_id'],$labels,UserConst::LABEL_TYPE_EMPLOYER);
        $extra ['label'] = $labels ? array_values($labels->toArray()) : [];
        $extra ['business_no'] = $orderNo;
        $commentEntity = new CommentEntity();
        $commentEntity->userId = $task['helper_user_id'];
        $commentEntity->toUserId = $task['user_id'];
        $commentEntity->title = '任务-帮手评价雇主';
        $commentEntity->content = $content;
        $commentEntity->score = $star ? $star : 5;
        $commentEntity->subType = MessageConst::TYPE_COMMENT_TASK_EMPLOYER;
        $commentEntity->businessId = $orderNo;
        $commentEntity->extra = $extra;
        $commentEntity->attachmentList = $attachList;
        $messageId = $commentBridge->addComment($commentEntity);
        if($messageId <= 0){
            throw new BusinessException('评价失败');
        }
        $task->comment_id = $messageId;
        $task->save();

        $avgScore = $commentBridge->avgUserLatestCommentScore($commentEntity->toUserId,MessageConst::TYPE_COMMENT_TASK_EMPLOYER);
        if($avgScore > 0){
           $user = $this->getUserService();
           $user->updateUserBaseInfo($commentEntity->toUserId,'','',$avgScore,null);
        }
        return $messageId;
    }

    /**
     * 评价帮手
     *
     * @param $orderNo
     * @param $userId
     * @param $content
     * @param $star
     * @param array $labels
     * @param array $attachList
     * @throws BusinessException
     * @return int
     */
    public function commentReceiver($orderNo,$userId,$content,$star,array $labels = [],array $attachList = []){
        $task = $this->check($orderNo,$star,$content,$labels,$attachList);
        $receiver = $this->getReceiveModel()->getOrderHelper($orderNo,$task['helper_user_id']);
        if(empty($receiver)){
            throw new BusinessException('帮手不存在');
        }
        if($task['user_id'] != $userId){
            throw new BusinessException('您不能评价其它人的任务');
        }
        if($receiver['comment_id'] > 0){
            throw new BusinessException('您已评价，不用再评价');
        }
        $commentBridge = $this->getCommentBridge();
        $labelBridge = $this->getLabelBridge();
        $labels = $labelBridge->saveUserLabel($task['helper_user_id'],$labels,UserConst::LABEL_TYPE_HELPER);
        $extra ['label'] = $labels ? array_values($labels->toArray()) : [];
        $extra ['business_no'] = $orderNo;
        $commentEntity = new CommentEntity();
        $commentEntity->userId = $task['user_id'] ;
        $commentEntity->toUserId = $task['helper_user_id'];
        $commentEntity->title = '任务-雇主评价帮手';
        $commentEntity->content = $content;
        $commentEntity->score = $star ? $star : 5;
        $commentEntity->subType = MessageConst::TYPE_COMMENT_TASK_HELPER;
        $commentEntity->businessId = $orderNo;
        $commentEntity->extra = $extra;
        $commentEntity->attachmentList = $attachList;
        $messageId = $commentBridge->addComment($commentEntity);
        if($messageId <= 0){
            throw new BusinessException('评价失败');
        }
        $receiver->comment_id = $messageId;
        $receiver->save();

        $avgScore = $commentBridge->avgUserLatestCommentScore($commentEntity->toUserId,MessageConst::TYPE_COMMENT_TASK_HELPER);
        if($avgScore > 0){
            $user = $this->getUserService();
            $user->updateUserBaseInfo($commentEntity->toUserId,'','',null,$avgScore);
        }
        return $messageId;
    }


    public function getOrderComment($orderNo,$commentType = MessageConst::TYPE_COMMENT_TASK_EMPLOYER,$isOwn = null){
        $commentService = $this->getCommentBridge();
        $list = $commentService->getCommentsByBusinessId($orderNo,$commentType,$isOwn,1,2);
        if(empty($list)){
            return [];
        }

        foreach ($list as $key=>$value){
            unset($list[$key]['created_time']);
            unset($list[$key]['updated_time']);
            unset($list[$key]['is_own']);
            unset($list[$key]['activity_at']);
            unset($list[$key]['m_sub_type']);
            unset($list[$key]['m_type']);
            unset($list[$key]['updated_at']);
            unset($list[$key]['business_id']);
        }
        return $list;
    }
    /**
     * @return CommentMessageService
     */
    protected function getCommentBridge(){
        return new CommentMessageBridge(new CommentMessageService());
    }

    /**
     * @return LabelService
     */
    protected function getLabelBridge(){
        return new LabelBridge(new LabelService());
    }

    /**
     * @param $star
     * @param $orderNo
     * @param $content
     * @param array $labels
     * @param array $attachList
     * @return Model
     * @throws BusinessException
     */
    protected function check($orderNo,$star,$content,array $labels = [],array $attachList =[] ){
        if($orderNo <= 0){
            throw new BusinessException('评价的任务不存在');
        }
        if(empty($content)){
            throw new BusinessException('评价内容不能为空');
        }
        if(mb_strlen($content) > 200){
            throw new BusinessException('评价内容不能超过200个文字');
        }
        $task = $this->getTaskOrderModel()->getByOrderNo($orderNo);
        if(empty($task)){
            throw new BusinessException('评价的任务不存在');
        }
        if($task['order_state'] != OrderConst::EMPLOYER_STATE_COMPLETE){
            throw new BusinessException('任务未完成，不能评价');
        }
        if(count($labels) > 3){
            throw new BusinessException('标签数不能超过3个');
        }
        if(count($attachList) > 3){
            throw new BusinessException('附件数不能超过3个');
        }
        if( $task['helper_user_id'] <= 0){
            throw new BusinessException('帮手不存在');
        }
        if($star > 5){
            throw new BusinessException('帮手评价不能超过5星');
        }

        return $task;
    }
}
