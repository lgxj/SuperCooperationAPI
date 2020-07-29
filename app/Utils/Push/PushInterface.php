<?php

namespace App\Utils\Push;

interface PushInterface
{
    /**
     * 绑定用户别名
     * @param string $alias 别名
     * @param string $cid 用户id
     * @return bool
     */
    public function bindAlias(string $alias, string $cid): bool;

    /**
     * 解除绑定用户别名
     * @param string $alias 别名
     * @param string $cid 用户ID
     * @return bool
     */
    public function unBindAlias(string $alias, string $cid): bool;

    /**
     * 为用户绑定（覆盖）标签
     * @param string $cid 用户ID
     * @param array $tagList 用户tag列表
     * @return bool
     */
    public function setUserTags(string $cid, array $tagList): bool;

    /**
     * 查询用户标签
     * @param string $cid 用户ID
     * @return array
     */
    public function getUserTags(string $cid): array;

    /**
     * 添加用户标签
     * @param string $cid 用户ID
     * @param string $tag 标签
     * @return bool
     */
    public function addUserTag(string $cid, string $tag): bool;

    /**
     * 移除用户标签
     * @param string $cid 用户ID
     * @param string $tag 标签
     * @return bool
     */
    public function removeUserTag(string $cid, string $tag): bool;

    /**
     * 为用户绑定手机号
     * @param string $cid 用户ID
     * @param string $phone 手机号
     * @return bool
     */
    public function bindCidPn(string $cid, string $phone): bool;

    /**
     * 解绑手机号
     * @param string $cid 用户ID
     * @return bool
     */
    public function unbindCidPn(string $cid): bool;

    /**
     * 添加黑名单用户
     * @param string $cid 用户ID
     * @return bool
     */
    public function addBlack(string $cid): bool;

    /**
     * 移除黑名单用户
     * @param string $cid 用户ID
     * @return bool
     */
    public function restoreBlack(string $cid): bool;

    /**
     * 创建消息体
     * @param string $title 消息标题
     * @param string $content 消息内容
     * @param array $payload 消息参数
     * @param int $sendType 推送方式。1：单推；2：批量推；3：群推
     * @param array $sms 短信补量
     * @param array $options 推送配置参数
     * @param int $messageType 消息类型。1：透传；2：通知-打开应用首页；3：通知-打开网页；4：通知-打开应用内页
     * @param bool $isOffline 是否保持离线消息
     * @param int $expireTime 过多久该消息离线失效
     * @return \IGtMessage
     */
    public function createMessage(string $title, string $content, array $payload = [], int $sendType = 1, array $sms = null, array $options = [], int $messageType = 1, bool $isOffline = true, int $expireTime = 3600): \IGtMessage;

    /**
     * 根据别名创建推着目标
     * @param string $alias 别名
     * @return \IGtTarget
     */
    public function createTargetByAlias(string $alias): \IGtTarget;

    /**
     * 根据别名创建推着目标
     * @param string cid 客户端用户ID
     * @return \IGtTarget
     */
    public function createTargetByCid(string $cid): \IGtTarget;

    /**
     * 创建推送目标列表
     * @param array $arr 目标数组
     * @param int $type 类型。1：别名；2：cid
     * @return array
     */
    public function createTargetList(array $arr, int $type = 1): array;

    /**
     * 单推
     * @param \IGtSingleMessage $message 消息体
     * @param \IGtTarget $target 推送目标
     * @param string $requestId 请求ID，重发时需要
     * @return array
     */
    public function toSingle(\IGtSingleMessage $message, \IGtTarget $target, string $requestId = null): array;

    /**
     * 批量推送
     * @param \IGtListMessage $message 消息体
     * @param array $targetList 目标用户列表
     * @param string|null $taskGroupName 任务名
     * @return array
     */
    public function toList(\IGtListMessage $message, array $targetList, string $taskGroupName = null): array;

    /**
     * 群推
     * @param \IGtAppMessage $message 消息体
     * @param array $phoneTypeList 手机类型，ANDROID和IOS
     * @param array $provinceList 省份编号，参考http://docs.getui.com/files/region_code.data
     * @param array $tagList 标签
     * @param int $speed 定速推送 例如100，个推控制下发速度在100条/秒左右
     * @param string $pushTime 定时推送 格式要求为yyyyMMddHHmm 需要申请开通套餐
     * @return array
     */
    public function toApp(\IGtAppMessage $message, array $phoneTypeList = [], array $provinceList = [], array $tagList = [], int $speed = null, string $pushTime = null): array;

    /**
     * 查询定时推送任务
     * @param string $taskId 任务ID
     * @return array
     */
    public function getScheduleTask(string $taskId): array;

    /**
     * 删除定时推送任务
     * @param string $taskId
     * @return bool
     */
    public function delScheduleTask(string $taskId): bool;
}
