<?php
namespace App\Services\Permission;

use App\Bridges\User\UserBridge;
use App\Models\Permission\Cache\ResourceCache;
use App\Models\Permission\Cache\VisitCache;
use App\Services\ScService;
use App\Services\User\UserService;

class BasePermissionService extends ScService
{

    protected function clearUserCache($systemId,$subId){
        (new ResourceCache([$systemId,$subId]))->deleteAll();
        (new VisitCache([$systemId, $subId]))->deleteAll();
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return new UserBridge(new UserService());
    }
}
