<?php
namespace App\Models\Permission;

/**
 * 系统
 * @package app\Models\Permission
 */
class System extends BasePermission
{
    protected $table = 'system';

    protected $primaryKey = 'system_id';

    protected $fillable = ['system_name', 'domain', 'desc'];
}
