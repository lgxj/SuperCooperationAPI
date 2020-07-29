<?php


namespace App\Models\User;


use App\Models\ScModel;

class UserAddress extends BaseUser
{
    protected $table = 'user_address';

    protected $primaryKey = 'id';
}
