<?php

namespace App\Utils;

use App\Consts\ErrorCode\MessageErrorCode;
use App\Exceptions\BusinessException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tencent\TLSSigAPIv2;

/**
 * 腾讯IM即时聊天接口
 *
 * Class IM
 * @package App\Utils
 */
class IM
{
    private $appId;
    private $secret;

    private $adminIdentifier;

    static $apiURL = 'https://console.tim.qq.com/v4/';   // API地址
    static $OPPOChannelID = ''; // OPPO 手机 Android 8.0 以上的 NotificationChannel 通知适配字段。

    public function __construct()
    {
        $this->appId = config('im')[config('im.type')]['appId'];
        $this->secret = config('im')[config('im.type')]['secret'];
        $this->adminIdentifier = config('im')[config('im.type')]['adminIdentifier'];
    }

    /**
     * 单个帐号导入
     * @param string $identifier 用户标识
     * @param string $nick 昵称
     * @param string $faceUrl 头像
     * @param int $type 帐号类型，值0表示普通帐号，1表示机器人帐号
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function accountImport(string $identifier, string $nick, string $faceUrl = '', int $type = 0): array
    {
        $data = [
            'Identifier' => $identifier,
            'Nick' => $nick,
            'FaceUrl' => $faceUrl,
//            'Type' => $type
        ];
        return $this->_request('im_open_login_svc/account_import', $data);
    }

    /**
     * 删除账号
     * @param array $identifierItems 请求删除的帐号标识数组，最多100个
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws BusinessException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function accountDelete(array $identifierItems): array
    {
        if (count($identifierItems) > 100) {
            throw new BusinessException('最多同时删除100个',MessageErrorCode::IM_DELETE_LIMIT);
        }
        $data = [
            'DeleteItem' => collect($identifierItems)->map(function ($item) {
                return ['UserID' => $item];
            })
        ];
        return $this->_request('im_open_login_svc/account_delete', $data);
    }

    /**
     * 帐号登录态失效（踢下线）
     * @param string $identifier 用户标识
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function kick(string $identifier): array
    {
        $data = [
            'Identifier' => $identifier
        ];
        return $this->_request('im_open_login_svc/kick', $data);
    }

    /**
     * 单发单聊消息
     * @param string $toAccount 消息接收方标识
     * @param array $msgBody 消息体
     * @param array|null $offlinePushInfo 离线推送信息配置
     * @param string $fromAccount 消息发送方标识
     * @param int $syncOtherMachine 1：把消息同步到 From_Account 在线终端和漫游上；2：不同步
     * @param int $msgLifeTime 消息离线保存时长（单位：秒）
     * @param bool $async 是否异步发送
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function sendMsg(string $toAccount, array $msgBody, array $offlinePushInfo = null, string $fromAccount = '', int $syncOtherMachine = 1, int $msgLifeTime = 604800, $async = true)
    {
        $data = [
            'SyncOtherMachine' => $syncOtherMachine,
            'To_Account' => $toAccount,
            'MsgLifeTime' => $msgLifeTime,
            'MsgRandom' => random_int(1000, 9999),
            'MsgTimeStamp' => time(),
            'MsgBody' => $msgBody,
        ];
        if ($fromAccount) {
            $data['From_Account'] = $fromAccount;
        }
        if ($offlinePushInfo) {
            $data['OfflinePushInfo'] = $offlinePushInfo;
        }
        $result = $this->_request('openim/sendmsg', $data, 'post', $async);
        return $result;
    }

    /**
     * 批量发单聊消息
     * @param array $toAccounts 消息接收方用户标识数组（最多500个）
     * @param array $msgBody 消息体
     * @param array|null $offlinePushInfo 离线推送信息配置
     * @param string $fromAccount 指定消息发送方帐号
     * @param int $syncOtherMachine 1：把消息同步到 From_Account 在线终端和漫游上；2：不同步
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws BusinessException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function batchSendMsg(array $toAccounts, array $msgBody, array $offlinePushInfo = null, string $fromAccount = '', int $syncOtherMachine = 2)
    {
        if (count($toAccounts) > 500) {
            throw new BusinessException('批量发送对象最多500个',MessageErrorCode::IM_BATCH_SEND_LIMIT);
        }
        $data = [
            'SyncOtherMachine' => $syncOtherMachine,
            'To_Account' => $toAccounts,
            'MsgRandom' => time() . random_int(1000, 9999),
            'MsgBody' => $msgBody
        ];
        if ($fromAccount) {
            $data['From_Account'] = $fromAccount;
        }
        if ($offlinePushInfo) {
            $data['OfflinePushInfo'] = $offlinePushInfo;
        }
        return $this->_request('openim/batchsendmsg', $data, 'post', true);
    }

    /**
     * 获取用户在线状态
     * @param array $toAccounts 查询用户标识组（最多500个）
     * @return array
     * @throws BusinessException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function queryState(array $toAccounts): array
    {
        if (count($toAccounts) > 500) {
            throw new BusinessException('最多同时查询500个账号',MessageErrorCode::IM_BATCH_QUERY_LIMIT);
        }
        $data = [
            'To_Account' => $toAccounts
        ];
        return $this->_request('openim/querystate', $data);
    }

    /**
     * 设置用户资料
     * @param string $fromAccount 用户标识
     * @param array $params 设置项
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function portraitSet(string $fromAccount, array $params)
    {
        $profileItem = collect($params)->map(function ($value, $tag) {
            return [
                'Tag' => $tag,
                'Value' => $value
            ];
        });
        $data = [
            'From_Account' => $fromAccount,
            'ProfileItem' => array_values($profileItem->toArray())
        ];
        return $this->_request('profile/portrait_set', $data);
    }

    /**
     * 添加黑名单
     * @param string $fromAccount 请求为该 Identifier 添加黑名单
     * @param array $toAccounts 待添加为黑名单的用户 Identifier 列表，单次请求的 To_Account 数不得超过 1000
     * @return array
     * @throws BusinessException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function blackListAdd(string $fromAccount, array $toAccounts): array
    {
        if (count($toAccounts) > 1000) {
            throw new BusinessException('最多同时拉黑1000用户',MessageErrorCode::IM_BATCH_BLACK_LIMIT);
        }
        $data = [
            'From_Account' => $fromAccount,
            'To_Account' => $toAccounts
        ];
        return $this->_request('sns/black_list_add', $data);
    }

    /**
     * 设置全局禁言
     * @param string $setAccount 设置禁言配置的帐号
     * @param int $c2cMsgNoSpeakingTime 单聊消息禁言时间，单位为秒。等于0代表取消帐号禁言；最大值4294967295代表帐号被设置永久禁言；其它代表该帐号禁言时间
     * @param int $groupMsgNoSpeakingTime 群组消息禁言时间，单位为秒。等于0代表取消帐号禁言；最大值4294967295代表帐号被设置永久禁言；其它代表该帐号禁言时间
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setNoSpeaking(string $setAccount, int $c2cMsgNoSpeakingTime = 4294967295, int $groupMsgNoSpeakingTime = 4294967295): array
    {
        $data = [
            'Set_Account' => $setAccount,
            'C2CmsgNospeakingTime' => $c2cMsgNoSpeakingTime,
            'GroupmsgNospeakingTime' => $groupMsgNoSpeakingTime
        ];
        return $this->_request('openconfigsvr/setnospeaking', $data);
    }

    /**
     * 创建文本消息元素
     * @param string $text
     * @return array
     */
    public static function createTextElem(string $text): array
    {
        return [[
            'MsgType' => 'TIMTextElem',
            'MsgContent' => [
                'Text' => $text
            ]
        ]];
    }

    /**
     * 创建地理位置消息元素
     * @param float $latitude
     * @param float $longitude
     * @param string $desc
     * @return array
     */
    public static function createLocationElem(float $latitude, float $longitude, string $desc): array
    {
        return [[
            'MsgType' => 'TIMLocationElem',
            'MsgContent' => [
                'Desc' => $desc,
                'Latitude' => $latitude,
                'Longitude' => $longitude
            ]
        ]];
    }

    /**
     * 创建表情消息元素
     * @param int $index
     * @param string $data
     * @return array
     */
    public static function createFaceElem(int $index, string $data = ''): array
    {
        return [[
            'MsgType' => 'TIMFaceElem',
            'MsgContent' => [
                'Index' => $index,
                'Data' => $data
            ]
        ]];
    }

    /**
     * 创建自定义消息元素
     * @param string $desc
     * @param string $data
     * @param string $ext
     * @param string $sound
     * @return array
     */
    public static function createCustomElem(string $desc, string $data, string $ext = '', string $sound = ''): array
    {
        return [[
            'MsgType' => 'TIMCustomElem',
            'MsgContent' => [
                'Desc' => $desc,
                'Data' => $data,
                'Ext' => $ext,
                'Sound' => $sound
            ]
        ]];
    }

    /**
     * 离线推送 OfflinePushInfo对象
     * @param string $title 离线推送标题。
     * @param string $desc 离线推送内容。
     * @param string $ext 离线推送透传内容。
     * @param string $sound 离线推送声音文件路径。
     * @param int $apnsBadgeMode 苹果推送。为0表示需要计数，为1表示本条消息不需要计数，即右上角图标数字不增加。
     * @param string $apnsImage 该字段用于标识 APNs 携带的图片地址，当客户端拿到该字段时，可以通过下载图片资源的方式将图片展示在弹窗上。
     * @return array
     */
    public static function createOfflinePushInfo(string $title, string $desc, string $ext, string $sound = '', int $apnsBadgeMode = 0, string $apnsImage = ''): array
    {
        return [
            'PushFlag' => 0,
            'Title' => $title,
            'Desc' => $desc,
            'Ext' => $ext,
            'AndroidInfo' => [
                'Sound' => $sound,
                'OPPOChannelID' => self::$OPPOChannelID,
            ],
            'ApnsInfo' => [
                'BadgeMode' => $apnsBadgeMode,
                'Title' => $title,
                'SubTitle' => $desc,
                'Image' => $apnsImage
            ]
        ];
    }

    /**
     * 获取管理员UserSig
     * @return mixed|string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function _getAdminUserSig()
    {
        return $this->getUserSig($this->adminIdentifier);
    }

    /**
     * 获取UserSig
     * @param $identifier
     * @return mixed|string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getUserSig($identifier)
    {
        $cacheKey = 'im-userSig-' . $identifier;
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        $userSig = (new TLSSigAPIv2($this->appId, $this->secret))->genSig($identifier);
        Cache::set($cacheKey, $userSig, 86400);
        return $userSig;
    }

    /**
     * 请求腾讯即时通信IM接口
     * @param $action
     * @param $data
     * @param string $method
     * @param bool $async
     * @return bool|\GuzzleHttp\Promise\PromiseInterface|mixed|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function _request($action, $data, $method = 'post', $async = false)
    {
        try {
            $uri = self::$apiURL . $action;
            $client = new Client();
            $options = [
                'connect_timeout' => 5,
                'timeout' => 5,
            ];

            // 公共参数
            $query['sdkappid'] = $this->appId;
            $query['identifier'] = $this->adminIdentifier;
            $query['usersig'] = $this->_getAdminUserSig();
            $query['random'] = random_int(0,4294967295);
            $query['contenttype'] = 'json';

            $uri .= '?' . http_build_query($query);

            if ($method == 'get') {
                $options['query'] = $data;
            } else {
                $options['json'] = $data;
            }
            $options['verify'] = false;
            // 异步请求
            if ($async) {
                return $client->requestAsync($method, $uri, $options);
            }

            $response = $client->request($method, $uri, $options);
            $result = $response->getBody()->getContents();
            $result = json_decode($result, true);
            if (!$result || $result['ActionStatus'] == 'FAIL') {
                Log::error('IM REST API 请求失败：', [$action, $data, $method, $result]);
                return false;
            }
            return $result;
        } catch (\Exception $e) {
            Log::error('IM REST API 请求失败：' . $e->getMessage(), [$action, $data, $method]);
            throw new BusinessException($e->getMessage(),MessageErrorCode::IM_REQUEST_FAILED);
        }
    }
}
