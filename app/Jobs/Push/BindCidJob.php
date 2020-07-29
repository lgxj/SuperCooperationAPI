<?php
namespace App\Jobs\Push;

use App\Bridges\User\UserPushBridge;
use App\Facades\Push;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * cid与userId绑定
 * @package App\Jobs
 */
class BindCidJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $userId;
    protected $cid;

    /**
     * BindCidJob constructor.
     * @param int $userId 用户ID
     * @param int $cid 推送客户端cid
     */
    public function __construct($userId, $cid)
    {
        $this->userId = $userId;
        $this->cid = $cid;
    }

    public function handle(UserPushBridge $userPushBridge)
    {
        Log::info('bind cid', [$this->userId, $this->cid]);
        // cid原绑定用户
        $orgUser = $userPushBridge->getUserByCid($this->cid);
        if ($orgUser) {
            // 用户与cid绑定关系无变化
            if ($orgUser->user_id == $this->userId) {
                return true;
            }
            // 解除原userId与cid绑定
            Push::unBindAlias($orgUser->user_id, $this->cid);
            // 修改原用户cid为空
            $userPushBridge->updateCid($orgUser->user_id, '');
        }

        // 绑定当前用户ID与CID
        $user = $userPushBridge->getUserById($this->userId);

        // 绑定Cid
        Push::bindAlias($this->userId, $this->cid);

        // 第一次绑定
        if (!$user) {
            // 获取用户手机号并绑定到推送
            $phoneUser = $userPushBridge->findByUserPhone($this->userId);
            $phone = $phoneUser ? $phoneUser->grant_login_identify : '';
            if ($phone) {
                Push::bindCidPn($this->cid, $phone);
            }
            // 保存推送用户
            $userPushBridge->add($this->userId, $this->cid, $phone);
        } else {
            // 保存新的cid
            $userPushBridge->updateCid($this->userId, $this->cid);
            Push::bindCidPn($user->cid, $user->phone);
        }
    }
}
