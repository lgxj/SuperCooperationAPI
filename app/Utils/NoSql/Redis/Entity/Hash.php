<?php
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 14-9-16
 * Time: 下午4:45
 */

namespace App\Utils\NoSql\Redis\Entity;


use App\Utils\NoSql\Redis\Entity;
use App\Utils\NoSql\Redis\RedisException;

class Hash extends Entity {

    /**
     * @param $field
     * @param $value
     * @return mixed
     * @throws RedisException
     */
    public function save($field,$value){
        $this->checkKey();
        return $this->getRedisConnection()->hSet($this->key,$field, $value);
    }

    /**
     * @param $field
     * @return mixed
     * @throws RedisException
     */
    public function remove($field){
        $this->checkKey();
        return $this->getRedisConnection()->hDel($this->key,$field);
    }

    /**
     * @return mixed
     * @throws RedisException
     */
    public function getAllField(){
        $this->checkKey();
        return $this->getRedisConnection()->hGetAll($this->key);
    }

    /**
     * @param $key
     * @param string $field
     * @return mixed
     * @throws RedisException
     */
    public function getFieldByKey($key,$field = ''){
        if($field){
            return $this->getRedisConnection()->hGet($key,$field);
        }
        return $this->getRedisConnection()->hGetAll($key);
    }

    /**
     * @return mixed
     * @throws RedisException
     */
    public function count(){
        $this->checkKey();
        return $this->getRedisConnection()->hLen($this->key);
    }

    /**
     * @param $field
     * @return mixed
     * @throws RedisException
     */
    public function get($field){
        $this->checkKey();
        return $this->getRedisConnection()->hGet($this->key,$field);
    }

    /**
     * @param $field
     * @return mixed
     * @throws RedisException
     */
    public function contains($field) {
        return $this->getRedisConnection()->hExists($this->key, $field);
    }

}
