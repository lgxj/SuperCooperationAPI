<?php


namespace App\Services\Permission;


use App\Utils\NoSql\Redis\RedisException;

class AccessService
{

    /**
     * 检测接口访问能力
     *
     * @param $userId
     * @param $systemId
     * @param $subId
     * @param $path
     * @param $method
     * @return bool
     * @throws RedisException
     */
    public static function visit($userId,$systemId,$subId,$path,$method){
        if($userId <= 0 || empty($path) || empty($method)){
            return false;
        }
        $apiService = new ApiService();
        $apiId = $apiService->getByPath($systemId,$path,$method);
        if($apiId <= 0){
            return  true;
        }
        $roleService = new RoleService();
        return  $roleService->checkPermissionWithCache($userId,$systemId,$subId,$apiId);
    }


}
