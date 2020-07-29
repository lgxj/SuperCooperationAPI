<?php


namespace App\Models\Message\Im;


use App\Models\Message\BaseMessage;

class MessageImPrimary extends BaseMessage
{
    protected $table = 'message_im_primary';

    protected $primaryKey = 'im_primary_id';

    public $timestamps = false;
}
