<?php
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 14-9-16
 * Time: 下午4:18
 */

namespace App\Utils\NoSql\Redis\Table;


use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use App\Utils\NoSql\Redis\RedisException;

class SortedHashTable{

    protected $connectString = 'database.redis.cache';

    private $config    = null;
    /**
     * @var Connection
     */
    private $connection= null;
    private $keyPrefix = 'sht_';
    private $listSuffix= '_l';
    private $hashSuffix= '_h';

    const ORDER_DESC = 1 ;
    const ORDER_ASC  = 2 ;

    public function __construct()
    {

    }

    /**
     * @param $primaryKey
     * @param $secondaryKey
     * @param $content
     * @param int $expire
     * @return bool|int
     * @throws RedisException
     */
    public function update($primaryKey,$secondaryKey,$content,$expire=0)
    {
        if(!$primaryKey || !$secondaryKey || !$content){
            throw new RedisException("invalid parameter for SortedHashTable::insert()");
        }
        $redis = $this->getRedisConnection();
        if(!$redis){
            return false;
        }

        $listKey = $this->getListKey($primaryKey);
        $hashKey = $this->getHashKey($primaryKey);

        $hashIdx = $redis->hGet($hashKey,$secondaryKey);
        if($hashIdx){
            $this->deleteOne($primaryKey,$secondaryKey);
        }

        $id = $this->getLastId($redis,$listKey);

        $redis->zAdd($listKey,$id,$this->encode($content));
        $redis->hSet($hashKey,$secondaryKey,$id);
        if($expire){
            $redis->expire($this->getListKey($primaryKey),$expire);
            $redis->expire($this->getHashKey($primaryKey),$expire);
        }
        return $id;
    }

    /**
     * @param $primaryKey
     * @param $secondaryKey
     * @param $content
     * @param int $expire
     * @return bool|int
     * @throws RedisException
     */
    public function insert($primaryKey,$secondaryKey,$content,$expire=0)
    {
        if(!$primaryKey || !$secondaryKey || !$content){
            throw new RedisException("invalid parameter for SortedHashTable::insert()");
        }
        $redis = $this->getRedisConnection();
        if(!$redis){
            return false;
        }

        $listKey = $this->getListKey($primaryKey);
        $hashKey = $this->getHashKey($primaryKey);

        $hashIdx = $redis->hGet($hashKey,$secondaryKey);
        if($hashIdx){
            return $hashIdx;
        }

        $id = $this->getLastId($redis,$listKey);

        $redis->zAdd($listKey,$id,$this->encode($content));
        $redis->hSet($hashKey,$secondaryKey,$id);
        if($expire){
            $redis->expire($this->getListKey($primaryKey),$expire);
            $redis->expire($this->getHashKey($primaryKey),$expire);
        }
        return $id;
    }

    /**
     * @param $primaryKey
     * @param $data
     * @param int $expire
     * @return array|bool
     * @throws RedisException
     */
    public function inserts($primaryKey,$data,$expire=0)
    {
        if(!$primaryKey || !$data || !is_array($data)){
            throw new RedisException("invalid parameter for SortedHashTable::inserts()");
        }
        $redis = $this->getRedisConnection();
        if(!$redis){
            return false;
        }

        $listKey = $this->getListKey($primaryKey);
        $hashKey = $this->getHashKey($primaryKey);
        /**

        $data    = $this->removeExistsKeys($data,$redis,$hashKey);
        if(!$data){
            return true;
        }
         ***/

        $id      = $this->getLastId($redis,$listKey);
        $query   = [$listKey];
        $map     = [];
        foreach($data as $k => $v){
            $query[] = $id;
            $query[] = $this->encode($v);
            $k = (isset($v['id']) && $v['id']) ? $v['id'] : $k;
            $map[$k] = $id;

            $id++;
        }

        call_user_func_array([$redis,'zAdd'],$query);
        $redis->hMset($hashKey,$map);
        if($expire){
            $redis->expire($this->getListKey($primaryKey),$expire);
            $redis->expire($this->getHashKey($primaryKey),$expire);
        }

        return $map;
    }

    /**
     * @param $primaryKey
     * @param $secondaryKeys
     * @return array|false
     * @throws RedisException
     */
    public function exists($primaryKey,$secondaryKeys)
    {
        if(!$primaryKey || !$secondaryKeys){
            throw new RedisException("invalid parameter for SortedHashTable::deleteBatch()");
        }
        $redis = $this->getRedisConnection();
        if(!$redis){
            return [];
        }

        $hashKey = $this->getHashKey($primaryKey);

        if(!is_array($secondaryKeys)){
            return $redis->hGet($hashKey,$secondaryKeys);
        }

        $keyExists = $redis->hMGet($hashKey,$secondaryKeys);
        return array_combine($secondaryKeys,$keyExists);
    }

    /**
     * @param $primaryKey
     * @param $secondaryKey
     * @return mixed|null
     * @throws RedisException
     */
    public function selectOne($primaryKey,$secondaryKey)
    {
        if(!$primaryKey || !$secondaryKey){
            throw new RedisException("invalid parameter for SortedHashTable::selectOne()");
        }
        $redis = $this->getRedisConnection();
        if(!$redis){
            return null;
        }

        $listIndex = $redis->hGet($this->getHashKey($primaryKey),$secondaryKey);
        if(!is_numeric($listIndex)){
            return null;
        }

        $data = $redis->zRangeByScore($this->getListKey($primaryKey),$listIndex,$listIndex);
        if(!$data || !isset($data[0]) || !$data[0]){
            return null;
        }
        return $this->decode($data[0]);
    }

    /**
     * @param $primaryKey
     * @param int $limit
     * @param int $offset
     * @param int $orderBy
     * @return array|mixed
     * @throws RedisException
     */
    public function selectAll($primaryKey,$limit=0,$offset=0,$orderBy=SortedHashTable::ORDER_DESC)
    {
        if(!$primaryKey){
            throw new RedisException("invalid parameter for SortedHashTable::selectAll()");
        }
        $redis = $this->getRedisConnection();
        if(!$redis){
            return [];
        }

        $offset = $this->parseOffset($limit,$offset,$orderBy);
        $listKey = $this->getListKey($primaryKey);

        $list    = $redis->zRange($listKey,$offset['start'],$offset['end']);

        if(self::ORDER_ASC === $orderBy){
            return $this->decodeList($list);
        }

        rsort($list);
        return $this->decodeList($list);
    }

    /**
     * @param $primaryKey
     * @param $secondaryKey
     * @return bool
     * @throws RedisException
     */
    public function deleteOne($primaryKey,$secondaryKey)
    {
        if(!$primaryKey || !$secondaryKey){
            throw new RedisException("invalid parameter for SortedHashTable::deleteOne()");
        }
        $redis = $this->getRedisConnection();
        if(!$redis){
            return false;
        }

        $listKey = $this->getListKey($primaryKey);
        $hashKey = $this->getHashKey($primaryKey);

        $listIndex = $redis->hGet($hashKey,$secondaryKey);
        if(!is_numeric($listIndex)){
            return true;
        }

        $redis->hDel($hashKey,$secondaryKey);
        $redis->zRemRangeByScore($listKey,$listIndex,$listIndex);

        return true;
    }

    /**
     * @param $primaryKey
     * @param array $secondaryKeys
     * @return bool
     * @throws RedisException
     */
    public function deleteBatch($primaryKey,$secondaryKeys=[])
    {
        if(!$primaryKey || !$secondaryKeys){
            throw new RedisException("invalid parameter for SortedHashTable::deleteBatch()");
        }

        foreach($secondaryKeys as $secondaryKey){
            $this->deleteOne($primaryKey,$secondaryKey);
        }

        return true;
    }

    /**
     * @param $primaryKey
     * @return bool
     * @throws RedisException
     */
    public function deleteAll($primaryKey)
    {
        if(!$primaryKey){
            throw new RedisException("invalid parameter for SortedHashTable::deleteAll()");
        }
        $redis = $this->getRedisConnection();
        if(!$redis){
            return false;
        }

        return $redis->del($this->getListKey($primaryKey), $this->getHashKey($primaryKey));
    }

    /**
     * @param $primaryKey
     * @return bool
     * @throws RedisException
     */
    public function count($primaryKey)
    {
        if(!$primaryKey){
            throw new RedisException("invalid parameter for SortedHashTable::count()");
        }
        $redis = $this->getRedisConnection();
        if(!$redis){
            return false;
        }

        return $redis->zCount($this->getListKey($primaryKey),'-inf','+inf');
    }

    /**
     *
     * @ret array data = [
     *      'secondaryKey1' => 'autoIncrementId1',
     *      'secondaryKey2' => 'autoIncrementId2',
     *      'secondaryKey3' => 'autoIncrementId3',
     *      ]
     */
    public function selectAllSecondaryKeys($primaryKey)
    {
        if(!$primaryKey){
            throw new RedisException("invalid parameter for SortedHashTable::selectAllSecondaryKeys()");
        }
        $redis = $this->getRedisConnection();
        if(!$redis){
            return [];
        }
    }


    /**
     * @param $data
     * @param $redis
     * @param $hashKey
     * @return mixed
     */
    private function removeExistsKeys($data,$redis,$hashKey)
    {
        $keys    = array_keys($data);

        $keyExists = $redis->hMGet($hashKey,$keys);
        $keyMap  = array_flip($keys);
        foreach($keyExists as $idx => $value){
            if($value) continue;

            $key = $keyMap[$idx];
            unset($data[$key]);
        }

        return $data;
    }

    private function parseOffset($limit=0,$offset=0,$orderBy=self::ORDER_DESC)
    {
        $result = [
            'start'     =>  -1 * $offset - $limit,
            'end'       => -1 * $offset - 1,
        ];

        if(self::ORDER_ASC === $orderBy){
            $result = [
                'start'     => $offset,
                'end'       => $offset + $limit-1,
            ];
            if(0 == $limit){
                $result['end'] = -1;
            }
        }

        return $result;
    }

    private function encode($content)
    {
        if(is_array($content)){
            return json_encode($content);
        }
        return $content;
    }

    private function decode($content)
    {
        if(preg_match('/^\s*[\[|\{].*[\]|\}\s*$]/',$content)){
            return json_decode($content,true);
        }

        return $content;
    }

    private function decodeList($list)
    {
        foreach($list as $idx => $row){
            $list[$idx] = $this->decode($row);
        }

        return $list;
    }

    private function getLastId($redis,$listKey)
    {
        $id = 1;
        $last = $redis->zRange($listKey,-1,-1,true);
        if($last){
            $scores = array_values($last);
            if(isset($scores[0])){
                $id = $scores[0] + 1;
            }
        }

        return $id;
    }

    private function getRedisConnection()
    {
        if(false !== $this->connection){
            return $this->connection;
        }
        $this->config = Config::get($this->connectString);
        if(empty($this->config)){
            throw new RedisException("Redis 配置文件不存在");
        }
        $connect = explode('.',$this->connectString);
        $this->connection = Redis::connection($connect[2]);
        return $this->connection;
    }

    private function getListKey($primaryKey)
    {
        return $this->keyPrefix . $primaryKey . $this->listSuffix;
    }

    private function getHashKey($primaryKey)
    {
        return $this->keyPrefix . $primaryKey . $this->hashSuffix;
    }

}
