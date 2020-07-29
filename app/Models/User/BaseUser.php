<?php


namespace App\Models\User;


use App\Models\ScModel;

class BaseUser extends ScModel
{
    protected $connection = 'sc_user';
}
