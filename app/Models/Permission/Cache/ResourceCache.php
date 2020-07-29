<?php


namespace App\Models\Permission\Cache;


use App\Utils\Json;
use App\Utils\NoSql\Redis\Table\STHashTable;

/**
 * system_id/sub_id
 * Class VisitCache
 * @package App\Models\Permission\Cache
 */
class ResourceCache  extends STHashTable
{
    protected  $expire = 172800; //过期时间2天

    protected  $prefixKey = 'perm:resource:%s:%s';

    public function cache($userId,array $resourceIds){
        $resourceIds = Json::encode($resourceIds);
        $attribute = ['ids'=>$resourceIds];
        $this->save($userId,$attribute);
    }


    public function resourceIds($userId){
        $attribute = $this->findByPk($userId);
        if(empty($attribute)){
            return [];
        }
        $attribute['ids'] = Json::decode($attribute['ids']);
        return $attribute['ids'] ? $attribute['ids'] : [];
    }
}
