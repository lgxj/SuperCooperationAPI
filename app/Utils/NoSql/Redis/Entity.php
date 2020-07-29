<?php
namespace App\Utils\NoSql\Redis;
use Illuminate\Redis\Connections\Connection;

/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 14-9-16
 * Time: 下午4:10
 */

abstract class Entity {

    /**
     * Redis 键
     * @var string
     */
    protected $key;
    /**
     * RedisConnect 对象
     * @var Connection
     */
    protected $connection;

    public function  __construct(Connection $connection){
        $this->setRedisConnection($connection);
    }

    public function setRedisConnection(Connection $connection) {
        $this->connection = $connection;
    }

    public function getRedisConnection(){
        if ($this->connection instanceof Connection) {
            return $this->connection;
        }else{
            throw new RedisException("Redis Connection lose");
        }

    }
    public function setKey($key){
        $this->key = $key;
    }
    public  function getKey(){
        return $this->key;
    }

    public  function checkKey(){
        if ($this->key === null) {
            throw new RedisException("不存在Key值");
        }
    }
    public abstract  function count();

    /**
     * @return mixed
     * @throws RedisException
     */
    public function ttl(){
        $this->checkKey();
        return $this->getRedisConnection()->ttl($this->key);
    }

    /**
     * @param $seconds
     * @return mixed
     * @throws RedisException
     */
    public function expire($seconds){
        $this->checkKey();
        return $this->getRedisConnection()->expire($this->key, $seconds);
    }

    /**
     * @param $unixTimeStamp
     * @return mixed
     * @throws RedisException
     */
    public function expireUnix($unixTimeStamp){
        $this->checkKey();
        return $this->getRedisConnection()->expireAt($this->key,$unixTimeStamp);
    }

    /**
     * @return mixed
     * @throws RedisException
     */
    public function exist(){
        $this->checkKey();
        return  $this->getRedisConnection()->exists($this->key);
    }

    /**
     * @return mixed
     * @throws RedisException
     */
    public function delete(){
      $this->checkKey();
      return  $this->getRedisConnection()->del($this->key);
    }

    /**
     * @return mixed
     * @throws RedisException
     */
    public function type(){
        $this->checkKey();
        return  $this->getRedisConnection()->type($this->key);
    }

    /**
     * @param $dstKey
     * @return mixed
     * @throws RedisException
     */
    public function rename($dstKey){
        $this->checkKey();
        return  $this->getRedisConnection()->rename($this->key,$dstKey);
    }
}
