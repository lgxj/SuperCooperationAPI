<?php
/**
 * 用户注册事件
 */
namespace App\Events\User;

use App\Models\User\User;
use Illuminate\Queue\SerializesModels;

class RegisterEvent
{
    use SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
