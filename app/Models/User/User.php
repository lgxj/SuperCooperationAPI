<?php

namespace App\Models\User;

use App\Events\User\RegisterEvent;
use App\Events\User\UpdatedEvent;

class User extends BaseUser
{
    protected $table = 'user';

    protected $primaryKey = 'user_id';

    protected $hidden = ['register_salt','pay_password'];

    /**
     * 模型的事件映射
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => RegisterEvent::class,
        'updated' => UpdatedEvent::class,
    ];
}
