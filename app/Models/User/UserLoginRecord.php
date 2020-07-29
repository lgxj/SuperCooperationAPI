<?php


namespace App\Models\User;

class UserLoginRecord extends BaseUser
{
    protected $table = 'user_login_record';

    protected $connection = 'sc_user';

    protected $primaryKey = 'login_record_id';

}
