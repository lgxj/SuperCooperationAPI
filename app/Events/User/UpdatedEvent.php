<?php
/**
 * 用户更新事件
 */
namespace App\Events\User;

use App\Models\User\User;
use Illuminate\Queue\SerializesModels;

class UpdatedEvent
{
    use SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
