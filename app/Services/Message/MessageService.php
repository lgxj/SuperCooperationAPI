<?php
namespace App\Services\Message;

use App\Bridges\User\UserBridge;
use App\Consts\DBConnection;
use App\Consts\ErrorCode\MessageErrorCode;
use App\Consts\GlobalConst;
use App\Consts\MessageConst;
use App\Exceptions\BusinessException;
use App\Models\Message\Im\MessageImPrimary;
use App\Models\Message\ImUser;
use App\Models\Message\Message;
use App\Models\Message\MessageCounter;
use App\Models\Message\MessageRelation;
use App\Models\Message\MessageReply;
use App\Models\Message\Reply;
use App\Services\User\UserService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use phpDocumentor\Reflection\Types\Boolean;

/**
 * 消息中心
 *
 * Class MessageService
 * @package App\Services\Message
 */
class MessageService
{

    protected $counterField = null;

    /**
     * @param array $message
     * @return mixed
     * @throws BusinessException
     */
    public function addMessage(array $message){

        $validate = Validator::make($message,[
            'user_id'=>'required|integer',
            'to_user_id'=>'required',
            'title'=>'required',
            'content'=>'required',
            'm_type'=>'required|integer',
            'm_sub_type'=>'required|integer',

        ],[
            'user_id.required' => '用户标识不能为空',
            'to_user_id.required' => '接收人标识不能为空',
            'title.required' => '标题不能为空',
            'content.required'=>"内容不能为空",
            'm_type.required' => '内容类型不能为空',
            'm_sub_type.required' => '内容子类型不能为空'

        ]);
        if($validate->fails()){
            throw new BusinessException($validate->errors()->first(),MessageErrorCode::CHECK_VALIDATION_ERROR);
        }

        $messageConnection = DBConnection::getMessageConnection();
        try {
            $messageConnection->beginTransaction();
            $messageModel = $this->getMessageModel(true);
            $fields = $messageModel->getTableColumns();
            foreach ($fields as $field) {
                if ($field == $messageModel->getKeyName()) {
                    continue;
                }
                if (isset($message[$field])) {
                    $messageModel->$field = $message[$field];
                }
            }
            $messageModel->save();
            $mid = $messageModel['mid'];
            if ($mid <= 0) {
                throw new BusinessException('创建消息体失败',MessageErrorCode::MESSAGE_CREATED_FAILED);
            }
            $list = [];
            $toUserIds = is_array($message['to_user_id']) ? $message['to_user_id'] : [$message['to_user_id']];
            foreach ($toUserIds as $toUserId) {
                $receiverRelation = [
                    'message_id' => $mid,
                    'user_id' => $toUserId,
                    'business_id' => $message['business_id'] ?? 0,
                    'm_type' => $message['m_type'],
                    'm_sub_type' => $message['m_sub_type'],
                    'is_own' => 0,
                    'state' => $message['state'] ?? 0,
                ];
                if (!empty($message['state'])) {
                    $receiverRelation['activity_at'] = Carbon::now();
                }
                $list[] = $receiverRelation;
            }
            if($message['user_id'] > 0) {
                //如通知类的关系就是一条
                $sendRelation = [
                    'message_id' => $mid,
                    'user_id' => $message['user_id'],
                    'business_id' => $message['business_id'] ?? 0,
                    'm_type' => $message['m_type'],
                    'm_sub_type' => $message['m_sub_type'],
                    'is_own' => 1,
                    'state' => $message['state'] ?? 1
                ];
                if (!empty($message['state'])) {
                    $sendRelation['activity_at'] = Carbon::now();
                }
                $list[] = $sendRelation;
            }
            $relationModel = $this->getMessageRelationModel(true);
            $relationModel->insert($list);
            if ($this->counterField) {
                $field = $this->counterField . '_unread_num';
                $counter = $this->getCounterModel(true);
                $counterUserIds = $counter->whereIn('user_id',$toUserIds)->select('user_id')->pluck('user_id')->toArray();
                foreach ($toUserIds as $key=>$toUserId){
                    if(!in_array($toUserId,$counterUserIds)){
                        $counterAdd =  $this->getCounterModel(true);
                        $counterAdd->user_id = $toUserId;
                        $counterAdd->{$field} = 1;
                        $counterAdd->save();
                        unset($toUserIds[$key]);
                    }
                }
                if($toUserIds) {
                    $counter->whereIn('user_id',$toUserIds)->increment($field, 1);
                }

            }
            $messageConnection->commit();
            return $mid;
        }catch (\Exception $e){
            $messageConnection->rollBack();
            Log::error('send message failed '.array_to_string($message));
            throw new BusinessException($e->getMessage(),MessageErrorCode::MESSAGE_CREATED_FAILED);
        }
    }

    public function avgUserLatestScore($userId, $type, $subType,$limit = 50){
        if($userId <= 0){
            return 0;
        }
        $rids = $this->getMessageRelationModel()->select('message_id')
            ->where('user_id', $userId)
            ->where('m_type', $type)
            ->where('is_own', 0)
            ->where('m_sub_type', $subType)
            ->orderByDesc('rid')
            ->limit($limit)
            ->pluck('message_id')
            ->toArray();
        if(empty($rids)){
            return 0;
        }
        return $this->getMessageModel()->whereIn('mid',$rids)->avg('score');
    }
    /**
     * @param $userId
     * @param $type
     * @param null $subType
     * @param int $isOwn
     * @param null $page
     * @param int $pageSize
     * @return LengthAwarePaginator
     */
    public function getListByPage($userId, $type, $subType = null,$isOwn=0,$page=null,$pageSize = GlobalConst::PAGE_SIZE)
    {
        if($userId <= 0){
            return null;
        }
        $model = $this->getMessageRelationModel()->select('rid')
            ->where('user_id', $userId)
            ->where('m_type', $type)
            ->where('is_own', $isOwn)
            ->orderByDesc('rid');//延迟关联/查询，解决慢查（file sort+分页limit）带来的性能问题

        $model->when(!is_null($subType),function ($query) use ($subType){
            $query->where('m_sub_type',$subType);
        });
        if(is_null($page)){
            $data = $model->paginate($pageSize);
        }else{
            $data = new LengthAwarePaginator($model->forPage($page,$pageSize)->get(),0,$pageSize,$page);
        }
        if(empty($data->items())){
            return $data;
        }
        $messageModel = $this->getMessageModel();
        $relationModel = $this->getMessageRelationModel();
        $userService = $this->getUserBridge();
        $rids =  collect($data->items())->pluck('rid')->toArray();
        $relations = $relationModel->whereIn('rid',$rids)->get();
        $messageIds = $relations->pluck('message_id')->toArray();
        $messages = $messageModel->whereIn('mid',$messageIds)->get()->keyBy('mid')->toArray();
        $relations = $relations->keyBy('rid')->toArray();

        $receiverUsers = [];
        if($isOwn){//isOwn 是发送方时
            $receiverUsers = $relationModel->whereIn('message_id',$messageIds)->where('is_own',0)->pluck('user_id','message_id')->toArray();
            $userIds = array_values($receiverUsers);
            $userIds = array_unique($userIds);
            $users = $userService->users($userIds);
        }else{
            $userIds = collect($messages)->pluck('user_id')->toArray();
            $userIds = array_unique($userIds);
            $users = $userService->users($userIds);
        }
        collect($data->items())->map(function (&$item) use ($relations,$messages,$users,$receiverUsers) {
            $relation = $relations[$item['rid']];
            $message = $messages[$relation['message_id']];
            $user = [];
            $toUserId = 0;
            if(isset( $users[$message['user_id']])){
                $toUserId = $message['user_id'];
                $user = $users[$toUserId];
            }elseif(isset($receiverUsers[$relation['message_id']])){
                $toUserId = $receiverUsers[$relation['message_id']];
                $user = $users[$toUserId];
            }
            $item['to_user_id'] = $toUserId;
            $this->formatMessage($item,$relation,$message,$user);
            return $item;
        });
        return $data;
    }

    public function getRidByMessageIdAndUid($messageId,$userId){
        if($messageId <= 0 || $userId <= 0){
            return 0;
        }
        $relation = $this->getMessageRelationModel()->select('rid')
            ->where('message_id', $messageId)
            ->where('user_id', $userId)
            ->first();
        if(empty($relation)){
            return 0;
        }
        return $relation['rid'];
    }

    public function getRidsByMessageIdAndUserIds($messageId,array $userIds){
        if($messageId <= 0 || empty($userIds)){
            return [];
        }
        $relations = $this->getMessageRelationModel()->select(['rid','user_id'])
            ->where('message_id', $messageId)
            ->whereIn('user_id', $userIds)
            ->get()
            ->pluck('rid','user_id')
            ->toArray();
        if(empty($relations)){
            return [];
        }
        return $relations;
    }


    public function getMessagesByBusinessId($businessId,$type,$subType = null,$isOwn = null,$page = 1,$pageSize = GlobalConst::PAGE_SIZE){
        if($businessId <= 0){
            return [];
        }
        $model = $this->getMessageRelationModel()->select('rid')
            ->where('business_id', $businessId)
            ->where('m_type', $type)
            ->orderByDesc('rid');//延迟关联/查询，解决慢查（file sort+分页limit）带来的性能问题
        $model->when(!is_null($subType),function ($query) use ($subType){
            $query->where('m_sub_type',$subType);
        });
        $model->when(!is_null($isOwn),function ($query) use ($isOwn){
            $query->where('is_own',$isOwn);
        });
        $list = $model->forPage($page,$pageSize)->get();
        if(empty($list)){
            return [];
        }
        $userService = $this->getUserBridge();
        $rids =  $list->pluck('rid')->toArray();
        $relations = $this->getMessageRelationModel()->whereIn('rid',$rids)->get();
        $messageIds = $relations->pluck('message_id')->toArray();
        $userIds = $relations->pluck('user_id')->toArray();
        $users = $userService->users($userIds);
        $messages = $this->getMessageModel()->whereIn('mid',$messageIds)->get()->keyBy('mid')->toArray();
        $relations = $relations->keyBy('rid')->toArray();
        collect($list)->map(function (&$item) use ($relations,$messages,$users) {
            $relation = $relations[$item['rid']] ?? [];
            $message = $messages[$relation['message_id']] ?? [];
            $user = $users[$relation['user_id']] ?? [];
            $this->formatMessage($item,$relation,$message,$user);
            return $item;
        });
        return $list->toArray();
    }
    /**
     * 获取消息详情
     * @param $id
     * @param int $userId
     * @return array
     * @throws BusinessException
     */
    public function getDetail($id, $userId = 0)
    {
        $detail = $this->getMessageRelationModel()->where('rid', $id)->select(['rid', 'message_id', 'user_id', 'm_type', 'm_sub_type', 'state', 'created_at'])->first();
        if (!$detail) {
            throw new BusinessException('消息未找到',MessageErrorCode::MESSAGE_NOT_EXIST);
        }
        $message = $this->getMessageModel()->find($detail['message_id']);
        $detail['title'] = $message['title'];
        $detail['content'] = $message['content'];
        $detail['score'] = $message['score'];
        $detail['attach_list'] = $message['attach_list'];
        $detail['extra'] = $message['extra'];
        // 设置已读
        if ($userId == $detail['user_id']) {
            $this->setRead($id,$userId,$detail['m_type']);
        }
        return $detail->toArray();
    }

    public function setRead($rid,$userId,$mType){
        if($rid <=0 || $userId <= 0){
            return false;
        }
        $readMap = [
            MessageConst::TYPE_NOTICE=>'notice',
            MessageConst::TYPE_ORDER => 'order'
        ];
        $relationModel = $this->getMessageRelationModel()->where('user_id', $userId)->where('rid', $rid)->first();
        if(empty($relationModel)){
            return false;
        }
        if($relationModel['state'] || $relationModel['is_own']){
            return true;
        }
        $relationModel->state = 1;
        $relationModel->activity_at = Carbon::now();
        $relationModel->save();
        $counter = $this->getCounterModel()->where('user_id', $userId)->first();
        if(isset($readMap[$mType]) && $counter){
            $counterField = $readMap[$mType].'_unread_num';
            $counter->decrement($counterField,1);
        }
        return true;
    }

    public function getMessageModel($isNew = false){
        return $isNew ? new Message() : Message::getModel();
    }

    public function getMessageRelationModel($isNew = false){
        return $isNew ? new MessageRelation() : MessageRelation::getModel();
    }

    public function getReplyModel($isNew = false){
        return $isNew ? new Reply() : Reply::getModel();
    }

    public function getCounterModel($isNew = false){
        return $isNew ? new MessageCounter() : MessageCounter::getModel();
    }

    protected function formatMessage( &$item,array $relation,array $message,array $user){
        $item['m_type'] = $relation['m_type'];
        $item['m_sub_type'] = $relation['m_sub_type'];
        $item['business_id'] = $relation['business_id'];
        $item['message_id'] = $relation['message_id'];
        $item['created_time'] = strtotime($relation['created_at']);
        $item['updated_time'] = strtotime($relation['updated_at']);
        $item['created_at'] = strtotime($relation['created_at']);
        $item['updated_at'] = strtotime($relation['updated_at']);
        $item['is_own'] = $relation['is_own'];
        $item['activity_at'] = $relation['activity_at'];
        $item['title'] = $message['title'];
        $item['content'] = $message['content'];
        $item['score'] = $message['score'];
        $item['attach_list'] = $message['attach_list'];
        $item['extra'] = $message['extra'];
        $item['user_id'] = $relation['user_id'];
        $item['user_name'] = $user['user_name'] ?? '';
        $item['is_certification'] = $user['is_certification'] ?? 0;
        $item['user_avatar'] = $user['user_avatar'] ?? '';
        $item['helper_level'] = $user['helper_level'] ?? 0;
        $item['employer_level'] = $user['employer_level'] ?? 0;
        $item['state'] = $relation['state'];
        return $item;
    }

    /**
     * @return UserService
     */
    protected function getUserBridge(){
        return new UserBridge(new UserService());
    }

    /**
     * 指定客服接待用户列表
     * @param $customerId
     * @param $page
     * @param $limit
     * @return array
     */
    public function getServiceUserList($customerId, $page, $limit)
    {
        // 指定客服接待用户总数
        $total = MessageRelation::where('user_id', $customerId)->where('m_type', 2)->count();

        // 查出指定客服聊天记录列表
        $messages = MessageRelation::where('user_id', $customerId)
            ->where('m_type', MessageConst::TYPE_CHAT)
            ->where('m_sub_type',MessageConst::TYPE_CHAT_IM_QQ)
            ->orderBy('message_relation.updated_at', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->select(['message_id', 'updated_at'])
            ->get();

        $data = $messages->keyBy('message_id')->toArray();

        // 查出聊天对象
        $primary = MessageImPrimary::whereIn('primary_mid', $messages->pluck('message_id'))->select(['primary_mid', 'send_user_id', 'to_user_id'])->get();
        $userIds = [];
        foreach ($primary as $item) {
            $userId = $item->send_user_id == $customerId ? $item->to_user_id : $item->send_user_id;
            $userIds[] = $userId;
            $data[$item->primary_mid]['to_user_id'] = $userId;
        }

        // 查询聊天对象信息
        $userInfo = ImUser::whereIn('user_id', $userIds)->select(['nick', 'user_id'])->get()->keyBy('user_id')->toArray();
        foreach ($data as &$item) {
            if(!isset($item['to_user_id'])){
                $item['user'] = [];
            }else {
                $item['user'] = $userInfo[$item['to_user_id']] ?? [];
            }
        }

        $list = array_values($data);
        return [
            'list' => $list,
            'total' => count($list)
        ];
    }

    /**
     * 指定聊天记录明细
     * @param $messageId
     * @param $order
     * @param $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getServiceMsgList($messageId, $order, $limit)
    {
        return MessageReply::where('main_message_id', $messageId)->select(['user_id', 'user_name', 'title', 'content', 'status', 'created_at'])->orderBy('created_at', $order)->paginate($limit);
    }

    /**
     * @param $userId
     * @param $rid
     * @param $isDeleteContent
     * @return bool
     * @throws \Exception
     */
    public function deleteMsgByRid($userId,$rid, $isDeleteContent = true){
        if($userId <= 0 || $rid <= 0){
            return false;
        }
        $relation = MessageRelation::where(['user_id'=>$userId,'rid'=>$rid])->first();
        if(empty($relation)){
            return false;
        }
        $messageId = $relation['message_id'];
        if(!$relation['state'] && !$relation['is_own']){
            $this->setRead($rid,$userId,$relation['m_type']);
        }
        $relation->delete();
        if($isDeleteContent && !MessageConst::isNotDeleteContent($relation['m_sub_type'])){
            Message::where('mid',$messageId)->delete();
        }
        return true;
    }

}
