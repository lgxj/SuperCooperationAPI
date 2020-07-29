<?php
/**
 * 客服表
 */
namespace App\Models\User;

class UserCustomer extends BaseUser
{
    protected $table = 'user_customer';

    protected $primaryKey = 'customer_id';

    const STATE_NORMAL = 1;     // 状态：正常
    const STATE_DISABLED = 0;   // 状态：停用
}
