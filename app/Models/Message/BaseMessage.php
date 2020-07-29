<?php
namespace App\Models\Message;

use App\Models\ScModel;

class BaseMessage extends ScModel
{
    protected $connection = 'sc_message';
}
