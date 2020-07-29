<?php
namespace App\Services\Message;

use App\Bridges\Permission\AdminBridge;
use App\Bridges\User\UserBridge;
use App\Consts\ErrorCode\MessageErrorCode;
use App\Consts\MessageConst;
use App\Exceptions\BusinessException;
use App\Models\Message\ImUser;
use App\Services\Permission\AdminService;
use App\Services\User\UserService;
use App\Utils\IM;
use App\Utils\UniqueNo;
use Illuminate\Support\Facades\Log;

/**
 * 腾讯即时聊天IM服务
 *
 * Class IMService
 * @package App\Services\Message
 */
class IMService extends BaseMessageService
{
    /**
     * @var UserService
     */
    protected $userBridge;

    /**
     * @var AdminService
     */
    protected $adminBridge;

    /**
     * @var IMUserService
     */
    protected $IMUserService;

    private $types = [
        1 => 'user',
        2 => 'admin',
        3 => 'system'
    ];

    public function __construct(UserBridge $userBridge, IMUserService $IMUserService, AdminBridge $adminBridge)
    {
        $this->userBridge = $userBridge;
        $this->IMUserService = $IMUserService;
        $this->adminBridge = $adminBridge;
    }

    /**
     * 绑定用户
     * @param $userId
     * @param $nick
     * @param $headImg
     * @param int $type
     * @return bool
     * @throws BusinessException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function bindUser($userId, $nick, $headImg, $type = 1)
    {
        $identifier = $this->types[$type] . '-' . $userId;
        if (!$this->IMUserService->find($identifier)) {
            $result = (new IM)->accountImport($identifier, $nick, $headImg);
            if ($result['ErrorCode']) {
                Log::error('创建IM账号失败', $result);
                throw new BusinessException('创建IM账号失败',MessageErrorCode::IM_CREATED_FAILED);
            }
            $this->IMUserService->add($userId, $nick, $headImg, $type, $identifier);
            return true;
        }
    }

    /**
     * 查询IM用户标识
     * @param $userId
     * @param int $type
     * @param bool $bind
     * @return array
     * @throws BusinessException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getLoginParams($userId, $type = 1, $bind = true)
    {
        $identifier = $this->types[$type] . '-' . $userId;
        $imUser = $this->IMUserService->find($identifier);

        $nick = '系统';
        $avatar = '';
        if ($type == 1) {
            $user = $this->userBridge->get($userId);
            if (!$user) {
                throw new BusinessException('IM用户不存在',MessageErrorCode::IM_USER_NOT_EXIST);
            }
            $nick = $user['user_name'];
            $avatar = $user['user_avatar'];
        } else if ($type == 2) {
            $user = $this->adminBridge->getInfo($userId);
            if (!$user) {
                throw new BusinessException('IM用户不存在',MessageErrorCode::IM_USER_NOT_EXIST);
            }
            $nick = $user['name'];
            $avatar = $user['avatar'];
        }

        $avatar = getFullPath($avatar);
        if (!$imUser && $bind) {
            $this->bindUser($userId, $nick, $avatar, $type);
        } else {
            $this->portraitSet($userId, $nick, $avatar, $type);
            (new IM)->portraitSet($identifier, [
                'Tag_Profile_IM_Nick' => $nick,
                'Tag_Profile_IM_Image' => $avatar
            ]);
        }

        $userSig = (new IM)->getUserSig($identifier);

        return [
            'userID' => $identifier,
            'userSig' => $userSig
        ];
    }

    /**
     * 更新IM用户信息
     * @param $userId
     * @param $nick
     * @param $avatar
     * @param int $type
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function portraitSet($userId, $nick, $avatar, $type = 1)
    {
        $identifier = $this->types[$type] . '-' . $userId;
        (new IM)->portraitSet($identifier, [
            'Tag_Profile_IM_Nick' => $nick,
            'Tag_Profile_IM_Image' => $avatar
        ]);
    }

    /**
     * 获取用户IM在线状态
     * @param $id
     * @return array
     */
    public function getUserOnlineState($id)
    {
        $res = (new ImUser)->where('identifier', $id)->select(['state'])->first();
        return $res ? $res->toArray() : ['state' => ''];
    }

    /**
     * IM回调
     * @param array $data
     * @return array
     */
    public function notify(array $data): array
    {
        $default = [
            'ActionStatus' => 'OK',
            'ErrorCode' => 0,
            'ErrorInfo' => ''
        ];
        if(!isset($data['CallbackCommand'])){
            return $default;
        }
        switch ($data['CallbackCommand']) {
            case 'State.StateChange':   // 状态变更回调
                return $this->_stateStateChange($data);
            case 'C2C.CallbackBeforeSendMsg':   // 发单聊消息之前回调
                return $this->_c2cCallbackBeforeSendMsg($data);
            case 'C2C.CallbackAfterSendMsg':    // 发单聊消息之后回调
                return $this->_c2cCallbackAfterSendMsg($data);
            default:
                return $default;
        }
    }

    /**
     * 状态变更回调
     * @param array $data
     * @return array
     */
    private function _stateStateChange(array $data): array
    {
        $identifier = $data['Info']['To_Account'];
        $action = $data['Info']['Action'];
        $reason = $data['Info']['Reason'];

        $this->IMUserService->update($identifier, [
            'state' => $action,
            'reason' => $reason
        ]);

        return $this->_callbackReturn();
    }

    /**
     * 单聊消息之前回调
     * @param array $data
     * @return array
     */
    private function _c2cCallbackBeforeSendMsg(array $data): array
    {
        // 是否允许发送
        $from = $data['From_Account'];
        $to = $data['To_Account'];
        $msgSeq = $data['MsgSeq']; // 消息序列号
        $msgBody = json_encode($data['MsgBody']);
        return $this->_callbackReturn();
    }

    /**
     * 单聊消息之后回调
     *
     * @param array $data
     * @return array
     * @throws BusinessException
     */
    private function _c2cCallbackAfterSendMsg(array $data): array
    {
        $userService = $this->getUserBridge();
        $fromUserId = $this->getUserIdByIdentify($data['From_Account']);
        $toUserId = $this->getUserIdByIdentify($data['To_Account']);
        if($fromUserId <= 0 || $toUserId <= 0){
            return $this->_callbackReturn();
        }
        $msgBody = $data['MsgBody'];
        $title = '';
        $content = '';
        $msgType = $msgBody[0]['MsgType'];
        $pushContent = '';
        $users = $userService->users([$fromUserId,$toUserId]);
        if(empty($users)){
            return $this->_callbackReturn();
        }
        if($msgType == 'TIMTextElem' ){
            $title = 'text';
            $content = $msgBody[0]['MsgContent']['Text'] ?? '';
            $pushContent = $content;
        }elseif($msgType == 'TIMCustomElem'){
            $title = 'address';
            $ext = $msgBody[0]['MsgContent']['Ext'];
            $ext = is_array($ext) ? $ext : json_decode($ext,true);
            $content = $ext['pic']  ?? '';
            $pushContent = '【'.$users[$fromUserId]['user_name'] . '】给您发送了一条定位消息';
        }elseif($msgType == 'TIMImageElem'){
            $title = 'pic';
            $content = $msgBody[0]['MsgContent']['ImageInfoArray'][0]['URL'] ?? '';
            $pushContent = '【'.$users[$fromUserId]['user_name'] . '】给您发送了一张图片';
        }
        $imService = new ImMessageService();
        $extra['user_name'] = $users[$fromUserId]['user_name'];
        $imService->addChat($fromUserId,$toUserId,$title,$content,[],$extra);
        $pushService = new PushService();
        $params = [
            'business_no'=>$toUserId,
            'notice_type'=>MessageConst::TYPE_CHAT,
            'notice_sub_type' => MessageConst::TYPE_CHAT_IM_QQ,
            'action' => 'toPage',
            'page' => '/pages/message/index',
            'query' => ['tab'=>1]
        ];
        $taskNo = UniqueNo::buildTelContactTaskNo($toUserId,MessageConst::TYPE_CHAT);
        $pushService->toSingle(MessageConst::TYPE_CHAT_IM_QQ, $toUserId, '您有一条新的聊天消息',$pushContent, $taskNo, $params);
        return $this->_callbackReturn();
    }

    /**
     * 消息回调应答
     * @param string $status
     * @param string $code
     * @param string $info
     * @return array
     */
    private function _callbackReturn($status = 'OK', $code = '0', $info = '处理成功')
    {
        return [
            'ActionStatus' => $status,
            'ErrorCode' => $code,
            'ErrorInfo' => $info
        ];
    }

    protected function getUserIdByIdentify($identify){
        $identify = explode('-',$identify);
        return $identify[1];
    }

    /**
     * @return UserService
     */
    protected function getUserBridge(){
        return new UserBridge(new UserService());
    }
}
