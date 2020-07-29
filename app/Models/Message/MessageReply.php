<?php
namespace App\Models\Message;

class MessageReply extends BaseMessage
{
    protected $table = 'message_reply';

    protected $primaryKey = 'reply_id';

}
