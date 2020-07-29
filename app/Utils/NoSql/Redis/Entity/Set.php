<?php
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 14-9-16
 * Time: 下午4:46
 */

namespace App\Utils\NoSql\Redis\Entity;

use App\Utils\NoSql\Redis\Entity;
use App\Utils\NoSql\Redis\RedisException;

class Set extends  Entity {

    /**
     * @param $value
     * @return mixed
     * @throws RedisException
     */
    public function add($value){
        $this->checkKey();
        return $this->getRedisConnection()->sAdd($this->key,$value);
    }

    /**
     * @param $value
     * @return mixed
     * @throws RedisException
     */
    public  function remove($value){
        $this->checkKey();
        return $this->getRedisConnection()->sRem($this->key,$value);
    }

    /**
     * @return mixed
     * @throws RedisException
     */
    public function pop(){
        $this->checkKey();
        return $this->getRedisConnection()->sPop($this->key);
    }

    /**
     * @return mixed
     * @throws RedisException
     */
    public function random() {
        $this->checkKey();
        return $this->getRedisConnection()->sRandMember($this->key);
    }

    /**
     * @return mixed
     * @throws RedisException
     */
    public function count(){
        $this->checkKey();
        return $this->getRedisConnection()->sCard($this->key);
    }

    /**
     * @return mixed
     * @throws RedisException
     */
    public function getData(){
        return $this->getRedisConnection()->sMembers($this->key);
    }

    /**
     * @param $value
     * @return mixed
     * @throws RedisException
     */
    public function contains($value) {
        $this->checkKey();
        return $this->getRedisConnection()->sIsMember($this->key, $value);
    }
}
