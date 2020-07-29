<?php
namespace App\Models\Permission;

class AdminRole extends BasePermission
{
    public $timestamps = false;
    protected $table = 'admin_role';

    protected $primaryKey = 'admin_role_id';
}
