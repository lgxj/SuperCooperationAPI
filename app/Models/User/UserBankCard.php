<?php


namespace App\Models\User;


use Illuminate\Database\Eloquent\SoftDeletes;

class UserBankCard extends BaseUser
{
    use SoftDeletes;

    protected $table = 'user_bank_card';

    protected $primaryKey = 'bank_id';
}
