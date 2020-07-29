<?php
/**
 * 用户登出事件
 */
namespace App\Events\User;

use App\Models\User\User;
use Illuminate\Queue\SerializesModels;

class LogoutEvent
{
    use SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
