<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * 消息推送Facade
 * @method static bool bindAlias(string $alias, string $cid)
 * @method static bool unBindAlias(string $alias, string $cid)
 * @method static bool setUserTags(string $cid, array $tagList)
 * @method static array getUserTags(string $cid)
 * @method static bool addUserTag(string $cid, string $tag)
 * @method static bool removeUserTag(string $cid, string $tag)
 * @method static bool bindCidPn(string $cid, string $phone)
 * @method static bool unbindCidPn(string $cid)
 * @method static bool addBlack(string $cid)
 * @method static bool restoreBlack(string $cid)
 * @method static \IGtMessage createMessage(string $title, string $content, array $payload = [], int $sendType = 1, array $sms = null, array $params = [], int $messageType = 1, bool $isOffline = true, int $expireTime = 3600)
 * @method static \IGtTarget createTargetByAlias(string $alias)
 * @method static \IGtTarget createTargetByCid(string $cid)
 * @method static array createTargetList(array $arr, int $type = 1)
 * @method static array toSingle(\IGtSingleMessage $message, \IGtTarget $target, string $requestId = null)
 * @method static array toList(\IGtListMessage $message, array $targetList, string $taskGroupName = null)
 * @method static array toApp(\IGtAppMessage $message, array $phoneTypeList = [], array $provinceList = [], array $tagList = [], int $speed = null, string $pushTime = null)
 * @method static array getScheduleTask(string $taskId)
 * @method static bool delScheduleTask(string $taskId)
 * @method static bool getAlias(int $userId)
 */
class Push extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'push';
    }
}
