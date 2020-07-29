<?php


namespace App\Services\Message;


use App\Consts\ErrorCode\MessageErrorCode;
use App\Consts\GlobalConst;
use App\Consts\MessageConst;
use App\Exceptions\BusinessException;
use App\Models\Message\Im\ImMessageRelation;
use App\Models\Message\Im\MessageImPrimary;
use App\Models\Message\MessageRelation;
use App\Models\Message\Reply;
use Carbon\Carbon;

/**
 * 腾讯即时聊天IM内容存档
 *
 * Class ImMessageService
 * @package App\Services\Message
 */
class ImMessageService extends MessageService
{

    /**
     * 腾讯云 IM消息
     * @param $sendUserId
     * @param $toUserId
     * @param $title
     * @param $content
     * @param array $attach
     * @param array $extra
     * @return mixed
     * @throws BusinessException
     */
    public function addChat($sendUserId,$toUserId,$title,$content,array $attach = [],$extra = []){
        if($sendUserId <= 0){
            throw new BusinessException('评论对像不存在',MessageErrorCode::IM_USER_NOT_FUND);
        }
        if($toUserId <= 0){
            throw new BusinessException('被评论对像不存在',MessageErrorCode::IM_TO_USER_NOT_FUND);
        }
        if(empty($title) && empty($content)){
            throw new BusinessException('评论内容不存在',MessageErrorCode::IM_CONTENT_EMPTY);
        }
        $message['user_id'] = $sendUserId;
        $message['to_user_id'] = $toUserId;
        $message['title'] = $title;
        $message['content'] = $content;
        $message['score'] = 0;
        $message['attach_list'] = $attach;
        $message['extra'] = $extra;
        $message['business_id'] = 0;
        $message['m_type'] = MessageConst::TYPE_CHAT;
        $message['m_sub_type'] = MessageConst::TYPE_CHAT_IM_QQ;
        $message['state'] = 1;

        $mainMessageId = $this->getMainMessageId($sendUserId,$toUserId);
        if($mainMessageId <= 0){
            $mainMessageId = $this->addMessage($message);
            $newPrimary = new MessageImPrimary();
            $newPrimary->send_user_id = $sendUserId;
            $newPrimary->to_user_id = $toUserId;
            $newPrimary->primary_mid = $mainMessageId;
            $newPrimary->save();
        }else{
            //主要是客服数据最新查询
            ImMessageRelation::where(['message_id'=>$mainMessageId,'m_type'=>$message['m_type']])->update(['updated_at'=>Carbon::now()]);
        }
        $replyMode = new Reply();
        $replyMode->main_message_id = $mainMessageId;
        $replyMode->user_id = $sendUserId;
        $replyMode->user_name = $extra['user_name'] ?? '';
        $replyMode->title = $title;
        $replyMode->content = $content ;
        $replyMode->status = 1;
        $replyMode->save();
        return $replyMode->reply_id;
    }
    public function listByPage($userId,$isOwn,$page,$pageSize = GlobalConst::PAGE_SIZE){
        return $this->getListByPage($userId,MessageConst::TYPE_CHAT,MessageConst::TYPE_CHAT_GENERAL,$isOwn,$page,$pageSize);
    }

    /**
     * IM一对主关系
     *
     * @param $sendUserId
     * @param $toUserId
     * @return int|mixed
     */
    protected function getMainMessageId($sendUserId,$toUserId){
        if($sendUserId <= 0 || $toUserId <= 0 ){
            return 0;
        }
        $primary = MessageImPrimary::where(['send_user_id'=>$sendUserId,'to_user_id'=>$toUserId])->first();
        if($primary){
            return $primary->primary_mid;
        }

        $primary = MessageImPrimary::where(['send_user_id'=>$toUserId,'to_user_id'=>$sendUserId])->first();
        if($primary){
            return $primary->primary_mid;
        }

        return 0;

    }
}
