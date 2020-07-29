<?php
namespace App\Models\Message;

class MessageCounter extends BaseMessage
{
    public $timestamps = false;

    protected $table = 'message_counter';

    protected $primaryKey = 'counter_id';
}
