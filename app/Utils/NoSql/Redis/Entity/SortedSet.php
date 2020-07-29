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

class SortedSet extends  Entity {

    /**
     * @param $value
     * @return mixed
     * @throws RedisException
     */
    public function add($value){
        $this->checkKey();
        return $this->getRedisConnection()->zAdd($this->key,time(),$value);
    }

    /**
     * @param $value
     * @return mixed
     * @throws RedisException
     */
    public  function remove($value){
        $this->checkKey();
        return $this->getRedisConnection()->zRem($this->key,$value);
    }

    /**
     * @return mixed
     * @throws RedisException
     */
    public function pop(){
        $this->checkKey();
        $value =  $this->getRedisConnection()->zRange($this->key,0,0);
        $this->getRedisConnection()->zrem($this->key,$value);
        return $value;
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
        return $this->getRedisConnection()->zCard($this->key);
    }

    /**
     * @return mixed
     * @throws RedisException
     */
    public function getData(){
        return $this->getRedisConnection()->zRange($this->key,0,-1);
    }

    /**
     * @param $page
     * @param int $limit
     * @param string $sorted
     * @return mixed
     * @throws RedisException
     */
    public function getDataByPage($page,$limit = 20,$sorted = 'asc'){
        $this->checkKey();
        $offset = ($page-1)*$limit;
        $stop = $offset+$limit;
        if(strtolower($sorted) == 'asc') {
            return $this->getRedisConnection()->zRange($this->key, $offset, $stop-1);
        }else{
            return $this->getRedisConnection()->zRevRange($this->key, $offset, $stop-1);
        }
    }

    /**
     * @param $value
     * @return bool
     * @throws RedisException
     */
    public function contains($value) {
        $this->checkKey();
        $score = (int)$this->getRedisConnection()->zScore($this->key, $value);
        return $score > 0 ? true : false;
    }
}
