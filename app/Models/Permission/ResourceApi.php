<?php


namespace App\Models\Permission;


class ResourceApi extends BasePermission
{
    public $timestamps = false;

    protected $table = 'resource_api';

    protected $primaryKey = 'resource_api_id';
}
