<?php
namespace App\Models\Message;

class PushTask extends BaseMessage
{
    protected $table = 'push_task';

    protected $primaryKey = 'task_id';

    protected $casts = [
        'payload' => 'array',
        'sms' => 'array',
        'options' => 'array'
    ];
}
