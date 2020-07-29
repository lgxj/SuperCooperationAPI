<?php

namespace App\Utils\Push;

use App\Consts\ErrorCode\MessageErrorCode;
use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\Log;

/**
 * 消息推送（个推）
 *
 * Class GeTui
 * @package App\Utils\Push
 */
class GeTui implements PushInterface
{

    protected $appId;
    protected $appKey;
    protected $appPackageName;
    protected $channel = 'channel';     // 通知渠道id，唯一标识，用户自定义
    protected $channelName = 'channel'; // 通知渠道名称，用户自定义
    protected $sound = '';      // 通知铃声文件名
    protected $logoName = '';   // 通知栏logo名
    protected $logoUrl = '';    // 通知栏logo路径
    protected $duration = 1440; // 通知下发时间段时长(分)
    protected $aliasPrefix;     // 别名前缀

    /**
     * @var \IGeTui
     */
    protected $push;

    public function __construct()
    {
        $this->appId = config('push.getui.appId');
        $this->appKey = config('push.getui.appKey');
        $this->appPackageName = config('push.getui.appPackageName');
        $this->logoName = config('push.logoName');
        $this->logoUrl = config('push.logoUrl');
        $this->sound = config('push.sound');
        $this->aliasPrefix = config('push.aliasPrefix');
        $this->_initPush();
    }

    /**
     * 绑定用户别名
     * @param string $alias 别名
     * @param string $cid 用户id
     * @return bool
     * @throws BusinessException
     */
    public function bindAlias($alias, $cid): bool
    {
        $alias = $this->getAlias($alias);
        $result = $this->push->bindAlias($this->appId, $alias, $cid);
        if ($result['result'] == 'ok') return true;
        Log::error('个推用户绑定别名失败', [['params' => [$alias, $cid]], ['result' => $result]]);
        throw new BusinessException('绑定别名失败',MessageErrorCode::PUSH_BIND_ALIAS_FAILED);
    }

    /**
     * 解除绑定用户别名
     * @param string $alias
     * @param string $cid
     * @return bool
     * @throws BusinessException
     */
    public function unBindAlias($alias, $cid): bool
    {
        $alias = $this->getAlias($alias);
        $result = $this->push->unBindAlias($this->appId, $alias, $cid);
        if ($result['result'] == 'ok') return true;
        Log::error('个推用户解绑别名失败', [['params' => [$alias, $cid]], ['result' => $result]]);
        throw new BusinessException('解绑别名失败',MessageErrorCode::PUSH_UN_BIND_ALIAS_FAILED);
    }

    /**
     * 为用户绑定标签（覆盖）
     * @param string $cid 用户ID
     * @param array $tagList 用户tag列表
     * @return bool
     * @throws BusinessException
     */
    public function setUserTags($cid, array $tagList): bool
    {
        $result = $this->push->setClientTag($this->appId, $cid, $tagList);
        if ($result['result'] == 'Success') return true;
        Log::error('个推为用户绑定标签失败', [['params' => [$cid, $tagList]], ['result' => $result]]);
        throw new BusinessException('绑定标签失败',MessageErrorCode::PUSH_BIND_LABEL_FAILED);
    }

    /**
     * 查询用户标签
     * @param $cid
     * @return mixed
     * @throws BusinessException
     */
    public function getUserTags($cid): array
    {
        $result = $this->push->getUserTags($this->appId, $cid);
        if ($result['result'] == 'ok') return $result['tags'];
        Log::error('个推查询用户标签失败', [['params' => $cid], ['result' => $result]]);
        throw new BusinessException('查询用户标签失败',MessageErrorCode::PUSH_QUERY_LABEL_FAILED);
    }

    /**
     * 添加用户标签
     * @param string $cid 用户ID
     * @param string $tag 标签
     * @return bool
     * @throws BusinessException
     */
    public function addUserTag($cid, $tag): bool
    {
        $tags = $this->getUserTags($cid);
        $tags = array_merge($tags, [$tag]);
        return $this->setUserTags($cid, $tags);
    }

    /**
     * 移除用户标签
     * @param string $cid 用户ID
     * @param string $tag 标签
     * @return bool
     * @throws BusinessException
     */
    public function removeUserTag($cid, $tag): bool
    {
        $tags = $this->getUserTags($cid);
        $tags = array_diff($tags, [$tag]);
        return $this->setUserTags($cid, $tags);
    }

    /**
     * 为用户绑定手机号
     * @param string $cid 用户ID
     * @param string $phone 手机号
     * @return bool
     * @throws BusinessException
     */
    public function bindCidPn($cid, $phone): bool
    {
        $params = [
            $cid => md5($phone)
        ];
        $result = $this->push->bindCidPn($this->appId, $params);
        if ($result['result'] == '0') return true;
        Log::error('个推为用户绑定手机号失败', [['params' => $params], ['result' => $result]]);
        throw new BusinessException('绑定手机失败',MessageErrorCode::PUSH_BIND_PHONE_FAILED);
    }

    /**
     * 解绑手机号
     * @param string $cid 用户ID
     * @return bool
     * @throws BusinessException
     */
    public function unbindCidPn($cid): bool
    {
        $result = $this->push->unbindCidPn($this->appId, [$cid]);
        if ($result['result'] == '0') return true;
        Log::error('个推解绑手机号失败', [['params' => $cid], ['result' => $result]]);
        throw new BusinessException('解绑手机失败',MessageErrorCode::PUSH_BIND_UN_PHONE_FAILED);
    }

    /**
     * 添加黑名单用户
     * @param string $cid 用户ID
     * @return bool
     * @throws BusinessException
     */
    public function addBlack($cid): bool
    {
        $result = $this->push->addCidListToBlk($this->appId, [$cid]);
        if ($result['result'] == 'success') return true;
        Log::error('个推添加黑名单用户失败', [['params' => $cid], ['result' => $result]]);
        throw new BusinessException('添加黑名单用户失败',MessageErrorCode::PUSH_BIND_BLACK_FAILED);
    }

    /**
     * 移除黑名单用户
     * @param string $cid 用户ID
     * @return bool
     * @throws BusinessException
     */
    public function restoreBlack($cid): bool
    {
        $result = $this->push->restoreCidListFromBlk($this->appId, [$cid]);
        if ($result['result'] == 'success') return true;
        Log::error('个推移除黑名单用户失败', [['params' => $cid], ['result' => $result]]);
        throw new BusinessException('移除黑名单用户失败',MessageErrorCode::PUSH_REMOVE_BLACK_FAILED);
    }

    /**
     * 创建消息体
     * @param string $title 消息标题
     * @param string $content 消息内容
     * @param array $payload 消息参数
     * @param int $sendType 推送方式。1：单推；2：批量推；3：群推
     * @param array|null $sms 短信补量
     * @param array $options 推送配置参数
     * @param int $messageType 消息类型。1：透传；2：通知-打开应用首页；3：通知-打开网页；4：通知-打开应用内页
     * @param bool $isOffline 是否保持离线消息
     * @param int $expireTime 过多久该消息离线失效
     * @return \IGtSingleMessage
     * @throws BusinessException
     */
    public function createMessage(string $title, string $content, array $payload = [], int $sendType = 1, array $sms = null, array $options = [], int $messageType = 1, bool $isOffline = true, int $expireTime = 3600): \IGtMessage
    {
        $bodge = $options['bodge'] ?? 0;     // IOS应用图标上显示的未读消息数量
        $sound = $options['sound'] ?? '';    // 通知铃声文件名
        $transmissionType = $options['transmissionType'] ?? 2;   // 收到消息是否立即启动应用，1为立即启动，2则广播等待客户端自启动
        $showStart = $options['showStart'] ?? '';    // 通知下发开始时间
        $showEnd = $options['showEnd'] ?? '';        // 通知下发结束时间
        $duration = $options['duration'] ?? 1440;    // 通知下发时间段时长（分钟），未设置结束时间时根据此值&showStart计算
        $logoName = $options['logoName'] ?? '';      // 通知栏Logo名
        $logoUrl = $options['logoUrl'] ?? '';        // 通知栏Logo路径
        $isRing = $options['isRing'] ?? '';          // 收到通知是否响铃
        $isVibrate = $options['isVibrate'] ?? '';    // 收到通知是否振动
        $url = $options['url'] ?? '';    // 网页路径或应用内页路径
        switch ($messageType) {
            case 1:
                $template = $this->_createTransTemplate($title, $content, $payload, $sms, $showStart, $showEnd, $duration, $bodge, $sound, $transmissionType);
                break;
            case 2:
                $template = $this->_createNotifyTemplate($title, $content, $payload, $sms, $logoName, $logoUrl, $transmissionType, $showStart, $showEnd, $duration, $isRing, $isVibrate, $bodge, $sound);
                break;
            case 3:
                $template = $this->_createLinkTemplate($title, $content, $payload, $url, $sms, $logoName, $logoUrl, $showStart, $showEnd, $duration, $isRing, $isVibrate, $bodge, $sound);
                break;
            case 4:
                $template = $this->_createStartActivityTemplate($title, $content, $payload, $url, $sms, $logoName, $logoUrl, $showStart, $showEnd, $duration, $isRing, $isVibrate, $bodge, $sound);
                break;
            default:
                throw new BusinessException('消息类型无效',MessageErrorCode::PUSH_MESSAGE_TYPE_ERROR);
        }

        switch ($sendType) {
            case 1:
                $message = new \IGtSingleMessage();
                break;
            case 2:
                $message = new \IGtListMessage();
                break;
            case 3:
                $message = new \IGtAppMessage();
                break;
            default:
                throw new BusinessException('推送方式无效',MessageErrorCode::PUSH_MESSAGE_METHOD_ERROR);
        }
        $message->set_isOffline($isOffline);
        $message->set_offlineExpireTime($expireTime * 1000);
        $message->set_data($template);

        return $message;
    }

    /**
     * 根据别名创建推着目标
     * @param string $alias 别名
     * @return \IGtTarget
     * @throws BusinessException
     */
    public function createTargetByAlias($alias): \IGtTarget
    {
        if (!$alias) {
            throw new BusinessException('推送目标别名必须',MessageErrorCode::PUSH_MESSAGE_ALIAS_ERROR);
        }
        $alias = $this->getAlias($alias);
        $target = new \IGtTarget();
        $target->set_appId($this->appId);
        $target->set_alias($alias);

        return $target;
    }

    /**
     * 根据别名创建推着目标
     * @param string cid 客户端用户ID
     * @return \IGtTarget
     * @throws BusinessException
     */
    public function createTargetByCid($cid): \IGtTarget
    {
        if (!$cid) {
            throw new BusinessException('推送目标ID必须', MessageErrorCode::PUSH_MESSAGE_CID_ERROR);
        }
        $target = new \IGtTarget();
        $target->set_appId($this->appId);
        $target->set_clientId($cid);

        return $target;
    }

    /**
     * 创建推送目标列表
     * @param array $arr 目标数组
     * @param int $type 类型。1：别名；2：cid
     * @return array
     * @throws BusinessException
     */
    public function createTargetList(array $arr = [], int $type = 1): array
    {
        if (count($arr) == 0) {
            throw new BusinessException('推送目标为空',MessageErrorCode::PUSH_MESSAGE_OBJECT_ERROR);
        }
        $list = [];
        switch ($type) {
            case 1:
                foreach ($arr as $val) {
                    $list[] = $this->createTargetByAlias($val);
                }
                break;
            case 2:
                foreach ($arr as $val) {
                    $list[] = $this->createTargetByCid($val);
                }
                break;
            default:
                throw new BusinessException('推送目标类型错误',MessageErrorCode::PUSH_MESSAGE_OBJECT_ERROR);
        }

        return $list;
    }

    /**
     * 单推
     * @param \IGtSingleMessage $message 消息体
     * @param \IGtTarget $target 推送目标
     * @param string $requestId 请求ID，重发时需要
     * @return array
     * @throws BusinessException
     */
    public function toSingle(\IGtSingleMessage $message, \IGtTarget $target, string $requestId = null): array
    {
        try {
            $result = $this->push->pushMessageToSingle($message, $target, $requestId);
            if ($result['result'] == 'ok') {
                return $result;
            } else {
                Log::error('个推单推失败', ['params' => [$message, $target, $requestId], 'result' => $result]);
                return [];
            }
        }catch(\RequestException $e){
            if ($requestId) {
                Log::error('个推单推失败', ['params' => [$message, $target, $requestId], 'result' => $e]);
                throw new BusinessException('推送失败',MessageErrorCode::PUSH_FAILED);
            }
            //失败时重发
            $requestId = $e->getRequestId();
            return $this->toSingle($message, $target,$requestId);
        }
    }

    /**
     * 批量推送
     * @param \IGtListMessage $message 消息体
     * @param array $targetList 目标用户列表
     * @param string|null $taskGroupName 任务名
     * @return array
     * @throws BusinessException
     */
    public function toList(\IGtListMessage $message, array $targetList, string $taskGroupName = null): array
    {
        $result = $this->push->getContentId($message, $taskGroupName);
        if ($result['result'] != 'ok') {
            throw new BusinessException('创建批量推送任务失败',MessageErrorCode::PUSH_FAILED);
        }
        $contentId = $result['contentId'];
        $result = $this->push->pushMessageToList($contentId, $targetList);
        if ($result['result'] == 'ok') {
            $result['taskId'] = $result['contentId'];
            return $result;
        }
        Log::error('个推批量推送失败', ['params' => [$message, $targetList, $taskGroupName], 'result' => $result]);
        throw new BusinessException('批量推送失败',MessageErrorCode::PUSH_FAILED);
    }

    /**
     * 群推
     * @param \IGtAppMessage $message 消息体
     * @param array $phoneTypeList 手机类型，ANDROID和IOS
     * @param array $provinceList 省份编号，参考http://docs.getui.com/files/region_code.data
     * @param array $tagList 标签
     * @param int $speed 定速推送 例如100，个推控制下发速度在100条/秒左右
     * @param string $pushTime 定时推送 格式要求为yyyyMMddHHmm 需要申请开通套餐
     * @return array|null
     * @throws BusinessException
     */
    public function toApp(\IGtAppMessage $message, array $phoneTypeList = [], array $provinceList = [], array $tagList = [], int $speed = null, string $pushTime = null): array
    {
        if ($pushTime) {
            $message->setPushTime($pushTime);
        }
        if ($speed) {
            $message->set_speed($speed);
        }

        $appList = [$this->appId];
        $message->set_appIdList($appList);

        $cdt = new \AppConditions();
        // 手机类型
        if (!empty($phoneTypeList)) {
            $cdt->addCondition3(\AppConditions::PHONE_TYPE, $phoneTypeList);
        }
        // 省市编码
        if (!empty($provinceList)) {
            $cdt->addCondition3(\AppConditions::REGION, $provinceList);
        }
        // 至少有个环境变化标签
        $tagList = $tagList ?: [];
        $tagList[] = config('env');
        $cdt->addCondition3(\AppConditions::TAG, $tagList);
        $message->set_conditions($cdt);

        $result = $this->push->pushMessageToApp($message);
        if ($result['result'] == 'ok') {
            $result['taskId'] = $result['contentId'];   // 统一返回参数名
            return $result;
        }
        Log::error('个推群推失败', ['params' => [$message, $phoneTypeList, $provinceList, $tagList, $pushTime, $speed], 'result' => $result]);
        throw new BusinessException('群推失败',MessageErrorCode::PUSH_FAILED);
    }

    /**
     * 查询定时推送任务
     * @param string $taskId 任务ID
     * @return array|bool
     */
    public function getScheduleTask($taskId): array
    {
        $result = $this->push->getScheduleTask($taskId, $this->appId);
        if ($result['result'] == 'success') {
            return $result['taskDetail'];
        }
        Log::error('个推查询定时推送任务失败', ['params' => $taskId, 'result' => $result]);
        return false;
    }

    /**
     * 删除定时推送任务
     * @param string $taskId
     * @return bool
     */
    public function delScheduleTask(string $taskId): bool
    {
        $result = $this->push->getScheduleTask($taskId, $this->appId);
        if ($result['result'] == 'success') {
            return true;
        }
        Log::error('个推查询定时推送任务失败', ['params' => $taskId, 'result' => $result]);
        return false;
    }

    /**
     * 【通知模板】打开应用首页
     * @param string $title 消息标题
     * @param string $content 消息内容
     * @param array $payload 消息参数
     * @param array|null $sms 短信补量
     * @param string $showStart 开始显示时间
     * @param string $showEnd 结束显示时间
     * @param int $duration 显示时间段区间时长(分钟，未传showEnd时根据showString和些参数计算)
     * @param int $bodge IOS应用Icon上未读消息数量
     * @param string $sound 通知音效文件
     * @param int $transmissionType 收到消息是否立即启动应用，1为立即启动，2则广播等待客户端自启动
     * @return \IGtTransmissionTemplate
     * @throws \Exception
     */
    protected function _createTransTemplate(string $title, string $content, array $payload = [], array $sms = null, string $showStart = '', string $showEnd = '', int $duration = 0, int $bodge = 0, string $sound = '', int $transmissionType = 2)
    {
        $transContent = json_encode([
            'messageTitle' => $title,
            'messageContent' => $content,
            'payload' => $payload
        ]);

        $template = new \IGtTransmissionTemplate();
        $template->set_appId($this->appId);
        $template->set_appkey($this->appKey);
        $template->set_transmissionType($transmissionType);
        $template->set_transmissionContent($transContent);

        // 定时下发
        if ($showStart) {
            if (!$showEnd) {
                $showEnd = date('Y-m-d H:i:s', strtotime("+$duration minute", strtotime($showStart)));
            }
            $template->set_duration($showStart, $showEnd);
        }

        //第三方厂商推送透传消息带通知处理
        $notify = $this->_getNotifyInfo($title, $content, $payload);
        $template->set3rdNotifyInfo($notify);

        // APNs
        $apnInfo = $this->_getApnInfo($title, $content, $payload, $bodge, $sound);
        $template->set_apnInfo($apnInfo);

        // 短信补量
        if (!empty($sms)) {
            $smsInfo = $this->_getSmsInfo($sms);
            $template->setSmsInfo($smsInfo);
        }

        return $template;
    }

    /**
     * 【通知模板】 打开应用首页
     * @param string $title 消息标题
     * @param string $content 消息内容
     * @param array $payload 消息参数
     * @param array|null $sms 短信补量
     * @param string $logoName 通知的图标名称，包含后缀名（需要在客户端开发时嵌入），如“push.png”
     * @param string $logoUrl 通知的图标url地址
     * @param int $transmissionType 收到消息是否立即启动应用：1为立即启动，2则广播等待客户端自启动
     * @param string $showStart 消息展示开始时间
     * @param string $showEnd 消息展示结束时间
     * @param int $duration 显示时间段区间时长(分钟，未传showEnd时根据showString和些参数计算)
     * @param bool $isRing 收到通知是否响铃
     * @param bool $isVibrate 收到通知是否振动
     * @param int $bodge IOS应用Icon上未读消息数量
     * @param string $sound 通知音效文件
     * @return \IGtNotificationTemplate
     * @throws \Exception
     */
    protected function _createNotifyTemplate(string $title, string $content, array $payload = [], array $sms = null, string $logoName = '', string $logoUrl = '', int $transmissionType = 2, string $showStart = '', string $showEnd = '', int $duration = 0, bool $isRing = true, bool $isVibrate = true, int $bodge = 0, string $sound = '')
    {
        $transContent = json_encode([
            'title' => $title,
            'content' => $content,
            'payload' => $payload
        ]);
        list($sound, $logoName, $logoUrl, $showStart, $showEnd) = $this->_getDefaultParams($sound, $logoName, $logoUrl, $showStart, $showEnd, $duration);

        $template = new \IGtNotificationTemplate();
        $template->set_appId($this->appId);
        $template->set_appkey($this->appKey);
        // 透传消息类型
        $template->set_transmissionType($transmissionType);
        // 透传内容
        $template->set_transmissionContent($transContent);
        // 通知栏标题
        $template->set_title($title);
        // 通知栏内容
        $template->set_text($content);
        // 通知栏logo
        $template->set_logo($logoName);
        // 通知栏logo链接
        $template->set_logoURL($logoUrl);
        // 是否响铃
        $template->set_isRing($isRing);
        // 是否震动
        $template->set_isVibrate($isVibrate);
        // 通知栏是否可清除
        $template->set_isClearable(true);
        // 设置ANDROID客户端在此时间区间内展示消息
        if ($showStart) {
            $template->set_duration($showStart, $showEnd);
        }

        // APNs
        $apnInfo = $this->_getApnInfo($title, $content, $payload, $bodge, $sound);
        $template->set_apnInfo($apnInfo);

        // 短信补量
        if (!empty($sms)) {
            $smsInfo = $this->_getSmsInfo($sms);
            $template->setSmsInfo($smsInfo);
        }

        return $template;
    }

    /**
     * 【通知模板】打开浏览器网页
     * @param string $title 消息标题
     * @param string $content 消息内容
     * @param array $payload 消息参数
     * @param string $url 要打开的网页地址
     * @param array|null $sms 短信补量
     * @param string $logoName 通知的图标名称，包含后缀名（需要在客户端开发时嵌入），如“push.png”
     * @param string $logoUrl 通知的图标url地址
     * @param string $showStart 消息展示开始时间
     * @param string $showEnd 消息展示结束时间
     * @param int $duration 显示时间段区间时长(分钟，未传showEnd时根据showString和些参数计算)
     * @param bool $isRing 收到通知是否响铃
     * @param bool $isVibrate 收到通知是否振动
     * @param int $bodge IOS应用Icon上未读消息数量
     * @param string $sound 通知音效文件
     * @return \IGtLinkTemplate
     * @throws \Exception
     */
    protected function _createLinkTemplate(string $title, string $content, array $payload = [], string $url = '', array $sms = null, string $logoName = '', string $logoUrl = '', string $showStart = '', string $showEnd = '', int $duration = 1440, bool $isRing = true, bool $isVibrate = true,int $bodge = 0, string $sound = '')
    {
        list($sound, $logoName, $logoUrl, $showStart, $showEnd) = $this->_getDefaultParams($sound, $logoName, $logoUrl, $showStart, $showEnd, $duration);

        $template = new \IGtLinkTemplate();
        $template->set_appId($this->appId);
        // 通知栏标题
        $template->set_title($title);
        // 通知栏内容
        $template->set_text($content);
        // 通知栏logo
        $template->set_logo($logoName);
        // 通知栏logo链接
        $template->set_logoURL($logoUrl);
        // 是否响铃
        $template->set_isRing($isRing);
        // 是否震动
        $template->set_isVibrate($isVibrate);
        // 通知栏是否可清除
        $template->set_isClearable(true);
        // 设置ANDROID客户端在此时间区间内展示消息
        if ($showStart) {
            $template->set_duration($showStart, $showEnd);
        }

        // 打开连接地址
        $template->set_url($url);

        // APNs
        $apnInfo = $this->_getApnInfo($title, $content, $payload, $bodge, $sound);
        $template->set_apnInfo($apnInfo);

        // 短信补量
        if (!empty($sms)) {
            $smsInfo = $this->_getSmsInfo($sms);
            $template->setSmsInfo($smsInfo);
        }

        return $template;
    }

    /**
     * 【通知模板】 打开应用内页面
     * @param string $title 消息标题
     * @param string $content 消息内容
     * @param array $payload 消息参数
     * @param string $url 要打开的应用页页面地址，以'/'开头
     * @param array|null $sms 短信补量
     * @param string $logoName 通知的图标名称，包含后缀名（需要在客户端开发时嵌入），如“push.png”
     * @param string $logoUrl 通知的图标url地址
     * @param string $showStart 消息展示开始时间
     * @param string $showEnd 消息展示结束时间
     * @param int $duration 显示时间段区间时长(分钟，未传showEnd时根据showString和些参数计算)
     * @param bool $isRing 收到通知是否响铃
     * @param bool $isVibrate 收到通知是否振动
     * @param int $bodge IOS应用Icon上未读消息数量
     * @param string $sound 通知音效文件
     * @return \IGtStartActivityTemplate
     * @throws \Exception
     */
    protected function _createStartActivityTemplate(string $title, string $content, array $payload = [], string $url = '', array $sms = null, string $logoName = '', string $logoUrl = '', string $showStart = '', string $showEnd = '', int $duration = 1440, bool $isRing = true, bool $isVibrate = true, int $bodge = 0, string $sound = '')
    {
        list($sound, $logoName, $logoUrl, $showStart, $showEnd) = $this->_getDefaultParams($sound, $logoName, $logoUrl, $showStart, $showEnd, $duration);
        $params = (collect($payload))->map(function ($val, $key) {
            return 'S.' . $key . '=' . $val;
        })->join(';');
        $intent = 'intent:#Intent;component=' . $this->appPackageName . $url . ';' . $params . ';end';

        $template = new \IGtStartActivityTemplate();
        $template->set_appId($this->appId);
        // 通知栏标题
        $template->set_title($title);
        // 通知栏内容
        $template->set_text($content);
        // 通知栏logo
        $template->set_logo($logoName);
        // 通知栏logo链接
        $template->set_logoURL($logoUrl);
        // 是否响铃
        $template->set_isRing($isRing);
        // 是否震动
        $template->set_isVibrate($isVibrate);
        // 通知栏是否可清除
        $template->set_isClearable(true);
        // 设置ANDROID客户端在此时间区间内展示消息
        if ($showStart) {
            $template->set_duration($showStart, $showEnd);
        }

        // 打开连接地址
        $template->set_intent($intent);

        // APNs
        $apnInfo = $this->_getApnInfo($title, $content, $payload, $bodge, $sound);
        $template->set_apnInfo($apnInfo);

        // 短信补量
        if (!empty($sms)) {
            $smsInfo = $this->_getSmsInfo($sms);
            $template->setSmsInfo($smsInfo);
        }

        return $template;
    }

    /**
     * 获取参数默认值
     * @param string $sound 通知音效文件路径
     * @param string $logoName 通知栏Logo名
     * @param string $logoUrl 通知栏Logo路径
     * @param string $showStart 通知下发开始时间
     * @param string $showEnd 通知下发结束时间
     * @param int $duration 通知时间段区间时长(分钟)
     * @return array
     */
    protected function _getDefaultParams(string $sound, string $logoName, string $logoUrl, string $showStart, string $showEnd, int $duration)
    {
        $sound = $sound ?: $this->sound;
        $logoName = $logoName ?: $this->logoName;
        $logoUrl = $logoUrl ?: $this->logoUrl;
        if ($showStart) {
            if (!$showEnd) {
                $duration = $duration ?: $this->duration;
                $showEnd = date('Y-m-d H:i:s', strtotime("+$duration minute", strtotime($showStart)));
            }
        }
        return [$sound, $logoName, $logoUrl, $showStart, $showEnd];
    }

    /**
     * @param $title
     * @param $content
     * @param $payload
     * @return \IGtNotify
     */
    public function _getNotifyInfo($title, $content, $payload): \IGtNotify
    {
        $params = 'S.title=' . $title . ';S.content=' . $content.';S.payload=' . json_encode($payload);
        $intent = 'intent:#Intent;launchFlags=0x14000000;action=android.intent.action.oppopush;package=' . $this->appPackageName . ';component=' . $this->appPackageName . '/io.dcloud.PandoraEntry;S.UP-OL-SU=true;' . $params . ';end';
        $notify = new \IGtNotify();
        $notify->set_title($title);
        $notify->set_content($content);
        $notify->set_intent($intent);
        $notify->set_payload(json_encode($payload));
        $notify->set_type(\NotifyInfo_Type::_intent);
        return $notify;
    }

    /**
     * APNs通知参数
     * @param string $title 通知标题
     * @param string $content 通知文本消息字符串
     * @param array $payload 自定义的数据
     * @param int $badge 应用icon上显示的数字
     * @param string $sound 通知铃声文件名
     * @return \IGtAPNPayload
     */
    protected function _getApnInfo(string $title, string $content, array $payload, int $badge = 1, string $sound = '')
    {
        $apn = new \IGtAPNPayload();
        $alertMsg = new \DictionaryAlertMsg();
        // 	通知文本消息字符串
        $alertMsg->body = $content;
        // (用于多语言支持）指定执行按钮所使用的Localizable.strings
         $alertMsg->actionLocKey = "ActionLockey";
        // 	(用于多语言支持）指定Localizable.strings文件中相应的key
         $alertMsg->locKey = "LocKey";
        // 如果loc-key中使用的占位符，则在loc-args中指定各参数
         $alertMsg->locArgs = array("locargs");
        // 指定启动界面图片名
         $alertMsg->launchImage = "launchimage";
        // 通知标题
        $alertMsg->title = $title;
        // (用于多语言支持）对于标题指定执行按钮所使用的Localizable.strings,仅支持iOS8.2以上版本
         $alertMsg->titleLocKey = "TitleLocKey";
        // 对于标题, 如果loc-key中使用的占位符，则在loc-args中指定各参数,仅支持iOS8.2以上版本
         $alertMsg->titleLocArgs = array("TitleLocArg");
        // 子标题,仅支持iOS8.2以上版本
        $alertMsg->subtitle = $content;
        // 当前本地化文件中的子标题字符串的关键字,仅支持iOS8.2以上版本
         $alertMsg->subtitleLocKey = "subtitleLocKey";
        // 当前本地化子标题内容中需要置换的变量参数 ,仅支持iOS8.2以上版本
         $alertMsg->subtitleLocArgs = array("subtitleLocArgs");

        $apn->alertMsg = $alertMsg;
        // 应用icon上显示的数字
        $apn->badge = $badge;
        // 通知铃声文件名
        $apn->sound = $sound;
        // 增加自定义的数据
        foreach ($payload as $key => $val) {
            $apn->add_customMsg($key, $val);
        }
        // 设置语音播报类型，int类型，0.不可用 1.播放body 2.播放自定义文本
        $apn->voicePlayType = 2;
        // 设置语音播报内容，String类型，非必须参数，用户自定义播放内容，仅在voicePlayMessage=2时生效
        $apn->voicePlayMessage = $title;
        // 	推送直接带有透传数据
        $apn->contentAvailable = 1;
        // 在客户端通知栏触发特定的action和button显示
        $apn->category = "ACTIONABLE";

        return $apn;
    }

    /**
     * 短信补量参数
     * @param array $sms 短信参数
     * @string $templateId 短信模板ID
     * @array $params 模板中占位符内容数组
     * @int $sendTime 推送后多久进行短信补发，(单位秒，1-72小时之间)
     * @bool $isAppLink 推送的短信模板中是否选用APPLink进行推送
     * @string $url 推送的短信模板中的APPLink链接地址
     * @string $payload 推送的短信模板中的APPLink自定义字段
     * @return \SmsMessage
     */
    protected function _getSmsInfo(array $sms)
    {
        $templateId = $sms['templateId'];
        $params = $sms['params'];
        $sendTime = $sms['sendTime'] ?? 3600;
        $isAppLink = $sms['isAppLink'] ?? false;
        $url = $sms['url'] ?? '';
        $payload = $sms['payload'] ?? '';

        $smsMessage = new \SmsMessage();
        // 推送的短信模板ID
        $smsMessage->setSmsTemplateId($templateId);
        // 推送的短信模板中占位符的内容
        $smsMessage->setSmsContent($params);
        // 推送后多久进行短信补发（单位：ms）
        $smsMessage->setOfflineSendtime($sendTime * 1000);
        // 	推送的短信模板中是否选用APPLink进行推送
        $smsMessage->setIsApplink($isAppLink);
        // 推送的短信模板中的APPLink链接地址
        $smsMessage->setUrl($url);
        // 推送的短信模板中的APPLink自定义字段
        $smsMessage->setPayload($payload);
        return $smsMessage;
    }

    /**
     * 别名处理
     * @param $alias
     * @return string
     */
    public function getAlias($alias)
    {
        return $this->aliasPrefix . $alias;
    }

    /**
     * 初始化个推实例
     */
    protected function _initPush()
    {
        $host = config('push.getui.host');
        $appKey = config('push.getui.appKey');
        $masterSecret = config('push.getui.masterSecret');
        $this->push = new \IGeTui($host, $appKey, $masterSecret);
    }
}
