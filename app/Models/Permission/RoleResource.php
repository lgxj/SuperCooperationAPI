<?php


namespace App\Models\Permission;


class RoleResource extends BasePermission
{
    public $timestamps = false;

    protected $table = 'role_resource';

    protected $primaryKey = 'role_resource_id';


}
