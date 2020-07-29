<?php
/**
 * 用户登录事件
 */
namespace App\Events\User;

use App\Models\User\User;
use Illuminate\Queue\SerializesModels;

class LoginEvent
{
    use SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
