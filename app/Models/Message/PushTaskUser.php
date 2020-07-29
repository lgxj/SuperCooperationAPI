<?php
namespace App\Models\Message;

class PushTaskUser extends BaseMessage
{
    protected $table = 'push_task_user';

    protected $primaryKey = 'task_user_id';
}
