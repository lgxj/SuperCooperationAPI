<?php
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 14-9-16
 * Time: 下午4:15
 */

namespace App\Utils\NoSql\Redis;
use App\Utils\NoSql\Redis\Entity\Hash;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

abstract class   Store {


    /**
     *  对象
     * @var Connection
     */
    protected $connection;

    /**
     * Redis Hash  数据类型操作对象
     * @var  Hash
     */
    protected $redisHash;


    protected $prefixKey = '';

    protected $config = array();

    protected $expire = 0;

    protected $connectString = 'database.redis.cache';

    public function  __construct($params = array(),$suffix = null){
        $this->initRelationKey($params,$suffix);
    }

    public function initRelationKey($params = array(),$suffix = null){
        if($params && !is_array($params)){
            $params = [$params];
        }
        if(empty($this->prefixKey)){
            $this->getCommonKey();
            foreach ($params as $param){
                $this->prefixKey .= ":%s";
            }
        }
        $this->prefixKey = call_user_func_array('sprintf', array_merge([$this->prefixKey], $params));
        if($suffix){
            $this->prefixKey .= ':'.$suffix;
        }
    }

    public  abstract function  insert($pk,array $attribute);
    public  abstract function  count();
    public  abstract function  save($pk,array $attribute);
    public  abstract function  delete($pk);
    public  abstract function  update($pk,array $attribute);
    public  abstract function  findByPk($pk);
    public  abstract function  findByPage($page,$pageSize=20,$sorted = 'asc');
    public  abstract function  findAll();
    public  abstract function  deleteAll();
    public  abstract function  contains($pk);

    public function setRedisConnection(Connection $connection) {
        $this->connection = $connection;
    }

    public function getRedisConnection(){
        if ($this->connection !== null) {
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




     /**
      * @return Entity\Hash
      * @throws RedisException
      */
    public function getRedisHash($pk){
        if ($this->redisHash === null) {
            $this->redisHash = new Entity\Hash($this->getRedisConnection());
        }

        $this->redisHash->setKey($this->getRedisKey($pk));
        return $this->redisHash;
    }




    /**
     * 获取存取Redis的Key
     * @param mixed $pk
     * @return string 返回Redis Key
     */
    public function getRedisKey($pk) {
        if(is_array($pk)){
            $pk = implode(':',$pk);
        }
        return $this->getCommonKey().':'.$pk;
    }


    public function getCommonKey(){

        if($this->prefixKey){
            return $this->prefixKey;
        }
        if(!$this->prefixKey){
            $class = get_class($this);
            if($pos = strrpos($class,'\\')){
                $this->prefixKey = strtolower(substr($class,$pos+1));
            }
        }
        return $this->prefixKey;

    }

}
