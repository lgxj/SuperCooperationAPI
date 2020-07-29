<?php
namespace App\Models\Permission\Cache;

use App\Models\Permission\Api;
use App\Utils\NoSql\Redis\RedisException;
use App\Utils\NoSql\Redis\Table\STHashTable;

class ApiCache extends STHashTable
{
    protected $prefixKey = 'perm:api:%s';

    protected $expire = 864000; //过期时间10天

    /**
     * 更新缓存
     * @param Api $api
     * @throws RedisException
     */
    public function edit(Api $api)
    {
        $pk = $api->path . $api->method;
        $this->save($pk,['api_name' => $api->name, 'api_id' => $api->api_id]);
    }

    /**
     * 删除
     * @param Api $api
     * @throws RedisException
     */
    public function del(Api $api)
    {
        $pk = $api->path . $api->method;
        $this->delete($pk);
    }


    /**
     * @param $path
     * @param $method
     * @return mixed
     * @throws RedisException
     */
    public function getByPath($path,$method){
        $pk = $path . $method;
        return $this->findByPk($pk);
    }


}
