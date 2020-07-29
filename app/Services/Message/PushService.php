<?php
namespace App\Services\Message;

use App\Bridges\User\UserPushBridge;
use App\Bridges\User\UserBridge;
use App\Consts\MessageConst;
use App\Facades\Push;
use App\Jobs\Push\BindCidJob;
use App\Jobs\Push\BindPhoneJob;
use App\Jobs\Push\PushJob;
use App\Models\Message\PushTask;
use App\Models\Message\PushTaskUser;
use App\Utils\UniqueNo;
use App\Services\User\UserPushService;
use App\Services\User\UserService;

/**
 *
 * 消息推送服务
 *
 * Class PushService
 * @package App\Services\Message
 *
 */
class PushService extends BaseMessageService
{

    /**
     * @var \App\Services\User\UserPushService
     */
    protected $userPushBridge;

    /**
     * @var \App\Services\User\UserService
     */
    protected $userBridge;

    public function __construct()
    {
        $this->userPushBridge = new UserPushBridge(new UserPushService());
        $this->userBridge = new UserBridge(new UserService());
    }

    /**
     * 是否已绑定推送客户端
     * @param int $userId
     * @return bool
     */
    public function isBind(int $userId)
    {
        $res = $this->userPushBridge->getUserById($userId);
        return $res && $res->cid;
    }

    /**
     * 绑定客户端推送ID
     * @param string $cid
     * @param int $userId
     * @return bool
     */
    public function bind(string $cid, int $userId)
    {
        if (!$cid || !$userId) return false;
        BindCidJob::dispatch($userId, $cid);
        return true;
    }

    /**
     * 绑定&更新手机号
     * @param $userId
     * @param $phone
     * @return bool
     */
    public function bindPhone($userId, $phone)
    {
        if (!$phone || !$userId) return false;
        BindPhoneJob::dispatch($userId, $phone);
        return true;
    }

    /**
     * 单推
     * @param int $code 推送业务标识
     * @param int $userId 推送目标用户ID
     * @param string $title 标题
     * @param string $content 内容
     * @param string $taskNo 本地任务流水号
     * @param array $payload 参数
     * @param array $sms 短信补量参数{"templateId":"xxx","params": [],...}
     * @param array $options 推送配置参数
     */
    public function toSingle($code, int $userId, string $title, string $content, string $taskNo = '', array $payload = [], array $sms = [], array $options = [])
    {
        list($payload, $sms, $options) = $this->_getDefaultParams($code, $payload, $sms, $options);
        PushJob::dispatch(MessageConst::PUSH_TYPE_SINGLE, $userId, $title, $content, $code, $taskNo, $payload, $sms, $options);
    }

    /**
     * 批量推送
     * @param int $code 推送业务标识
     * @param array $ids 推送目标用户ID数组
     * @param string $title 标题
     * @param string $content 内容
     * @param string $taskNo 本地任务流水号
     * @param array $payload 参数
     * @param array $sms 短信补量参数{"templateId":"xxx","params": [],...}
     * @param array $options 推送配置参数
     */
    public function toList($code, array $ids, string $title, string $content, string $taskNo = '', array $payload = [], array $sms = [], array $options = [])
    {
        list($payload, $sms, $options) = $this->_getDefaultParams($code, $payload, $sms, $options);
        PushJob::dispatch(MessageConst::PUSH_TYPE_LIST, $ids, $title, $content, $code, $taskNo, $payload, $sms, $options);
    }

    /**
     * 群推
     * @param int $code 推送业务标识
     * @param string $title 标题
     * @param string $content 内容
     * @param string $taskNo 本地任务流水号
     * @param array $payload 参数
     * @param null $phoneTypeList 手机类型：android/ios
     * @param array $tagList 标签
     * @param array $provinceList 省份编号，参考http://docs.getui.com/files/region_code.data
     * @param array $sms 短信补量参数{"templateId":"xxx","params": [],...}
     * @param array $options 推送配置参数
     */
    public function toApp($code, string $title, string $content, string $taskNo = '', array $payload = [], $phoneTypeList = null, array $tagList = [], array $provinceList = [], array $sms = [], array $options = [])
    {
        list($payload, $sms, $options) = $this->_getDefaultParams($code, $payload, $sms, $options);
        $target = [
            'phoneTypeList' => $phoneTypeList,
            'tagList' => $tagList,
            'provinceList' => $provinceList
        ];
        PushJob::dispatch(MessageConst::PUSH_TYPE_APP, $target, $title, $content, $code, $taskNo, $payload, $sms, $options);
    }

    /**
     * 根据code获取推送默认配置
     * @param string $code
     * @param array $payload
     * @param array $sms
     * @param array $options
     * @return array
     */
    protected function _getDefaultParams(string $code, array $payload, array $sms, array $options)
    {
        if (!$code) {
            return [$payload, $sms, $options];
        }

        $default = MessageConst::getConfig($code);
        if (!empty($default)) {
            return [
                array_merge($default['payload'], $payload),
                array_merge($default['sms'], $sms),
                array_merge($default['options'], $options)
            ];
        }
        return [$payload, $sms, $options];
    }

    /**
     * 保存推送任务信息
     * @param $code
     * @param $taskNo
     * @param $type
     * @param $target
     * @param $title
     * @param $content
     * @param $payload
     * @param $sms
     * @param $options
     * @param $result
     * @throws \App\Exceptions\BusinessException
     */
    public function saveTask($code, $taskNo, $type, $target, $title, $content, $payload, $sms, $options, $result)
    {
        $taskNo = $taskNo ?: UniqueNo::buildPushTaskNo();
        $model = new PushTask();
        $model->task_no = $taskNo;
        $model->code = $code;
        $model->type = $type;
        $model->title = $title;
        $model->content = $content;
        $model->payload = $payload;
        $model->sms = $sms;
        $model->options = $options;
        $model->result = $result['result'];
        $model->out_task_id = $result['taskId'];
        $model->save();

        switch ($type) {
            case MessageConst::PUSH_TYPE_SINGLE:
                $model = new PushTaskUser();
                $model->task_no = $taskNo;
                $model->user_id = $target;
                $model->status = $result['status'];
                $model->save();
                break;
            case MessageConst::PUSH_TYPE_LIST:
                foreach ($target as $userId) {
                    $model = new PushTaskUser();
                    $model->task_no = $taskNo;
                    $model->user_id = $userId;
                    $alias = Push::getAlias($userId);
                    $model->status = $result['aliasDetails'][$alias] ?? '';
                    $model->save();
                }
                break;
        }
    }
}
