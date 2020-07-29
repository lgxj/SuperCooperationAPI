<?php


namespace App\Models\Message;


class Reply extends BaseMessage
{
    protected $table = 'message_reply';

    protected $primaryKey = 'reply_id';
}
