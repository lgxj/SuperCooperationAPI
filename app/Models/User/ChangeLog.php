<?php


namespace App\Models\User;


class ChangeLog extends BaseUser
{
    protected $table = 'user_change_log';

    protected $primaryKey = 'log_id';
}
