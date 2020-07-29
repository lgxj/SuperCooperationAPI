<?php


namespace App\Models\Permission\Cache;


use App\Utils\NoSql\Redis\RedisException;
use App\Utils\NoSql\Redis\Table\STHashTable;

/**
 * system_id/sub_id
 * Class VisitCache
 * @package App\Models\Permission\Cache
 */
class VisitCache extends STHashTable
{
    protected  $expire = 172800; //过期时间2天

    protected  $prefixKey = 'perm:visit:%s:%s';

    public function cache($userId,$apiId,bool $permission){
        $attribute = [$apiId=>$permission];
        $this->save($userId,$attribute);
    }

    /**
     * @param int $userId
     * @param int $apiId
     * @return int 0表未命中缓存 1表示有权限 2表示没权限
     * @throws RedisException
     */
    public function permission($userId,$apiId){
       $attribute = $this->findByPk($userId);
       if(empty($attribute) || !isset($attribute[$apiId])){
           return 0;
       }
       return $attribute[$apiId] ? 1 : 2;
    }



}
