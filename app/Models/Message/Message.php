<?php
namespace App\Models\Message;

class Message extends BaseMessage
{
    protected $casts = ['attach_list' => 'json','extra'=>'json'];

    protected $table = 'message';

    protected $primaryKey = 'mid';
}
