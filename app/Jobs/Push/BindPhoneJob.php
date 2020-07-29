<?php
namespace App\Jobs\Push;

use App\Bridges\User\UserPushBridge;
use App\Facades\Push;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * 绑定推送手机号
 * @package App\Jobs
 */
class BindPhoneJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $userId;
    protected $phone;

    /**
     * BindPhoneJob constructor.
     * @param int $userId 用户ID
     * @param int|array $phone 手机号
     */
    public function __construct($userId, $phone)
    {
        $this->userId = $userId;
        $this->phone = $phone;
    }

    public function handle(UserPushBridge $userPushBridge)
    {
        $user = $userPushBridge->getUserById($this->userId);
        if ($user) {
            Push::bindCidPn($user->cid, $this->phone);
            $userPushBridge->updatePhone($this->userId, $this->phone);
        }
    }
}
