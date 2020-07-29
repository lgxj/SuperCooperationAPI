<?php
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 14-9-16
 * Time: 下午4:18
 */

namespace App\Utils\NoSql\Redis\Table;



use App\Utils\NoSql\Redis\Entity\SortedSet;
use App\Utils\NoSql\Redis\RedisException;
use App\Utils\NoSql\Redis\Store;

class STHashTable extends Store {

    /**
     * Redis Set 数据类型对象
     * @var SortedSet
     */
    protected $redisSet;

    /**
     * @param $pk
     * @param array $attribute
     * @return string
     * @throws RedisException
     */
    public function  insert($pk,array $attribute)
    {
        if(empty($attribute)){
            throw new  RedisException('字段不存在');
        }
        if(null == $pk){
            $pk = $this->count();
            $attribute['redis_id'] = $pk;
        }
        $hash = $this->getRedisHash($pk);
        $hashKey = $hash->getKey();
        $set =  $this->getRedisSet();
        $flag = $set->add($hashKey);
        if($flag <= 0){
            throw new RedisException("hashkey [{$hashKey}]已经存在");
        }
        $hash->delete();
        foreach ($attribute as $field=>$value){
            $hash->save($field,$value);
        }
        if($this->expire > 0){
            $hash->expire($this->expire);
            $set->expire($this->expire);
        }
        return $hashKey;

    }

    /**
     * @param $pk
     * @param array $attribute
     * @return string
     * @throws RedisException
     */
    public function save($pk,array $attribute){
        if(empty($attribute)){
            throw new  RedisException('字段不存在');
        }
        if(null == $pk){
            $pk = $this->count();
        }
        $hash = $this->getRedisHash($pk);
        $hashKey = $hash->getKey();
        $set =  $this->getRedisSet();
        $set->add($hashKey);
        foreach ($attribute as $field=>$value){
            $hash->save($field,$value);
        }
        if($this->expire > 0){
            $hash->expire($this->expire);
            $set->expire($this->expire);
        }
        return $hashKey;
    }

    /**
     * @param $pk
     * @param array $attribute
     * @return bool
     * @throws RedisException
     */
    public function update($pk ,array $attribute){
        $hash = $this->getRedisHash($pk);
        if($hash->exist()){
            foreach($attribute as $field=>$value){
                $hash->save($field,$value);
            }
            return true;
        }
        return false;
    }

    /**
     * @param $pk
     * @return mixed
     * @throws RedisException
     */
    public function findByPk($pk){
        $hash = $this->getRedisHash($pk);
        return $hash->getAllField();

    }

    /**
     * @return array
     * @throws RedisException
     */
    public function findAll(){
        $hash = $this->getRedisHash(null);
        $set =  $this->getRedisSet();
        $rows = array();
        $_datas= $set->getData();
        if(!$_datas) return $rows;
        foreach($_datas as $_data){
            $rows[$_data] = $hash->getFieldByKey($_data);
            if(empty($rows[$_data])){
                $set->remove($_data);
            }
        }
        return $rows;
    }

    /**
     * @param $pk
     * @return bool|mixed
     * @throws RedisException
     */
    public function delete($pk){
        $hash = $this->getRedisHash($pk);
        $hashKey = $hash->getKey();
        $set =  $this->getRedisSet();
        if($hash->delete()){
            return $set->remove($hashKey);
        }
        return false;
    }

    /**
     * @return mixed
     * @throws RedisException
     */
    public  function deleteAll(){
        $hash = $this->getRedisHash(null);
        $set =  $this->getRedisSet();
        $rows = 0;
        try {
            if ($rows = $hash->getRedisConnection()->del($set->getData())) {
                $set->getRedisConnection()->del($set->getKey());
            }
        }catch (\Exception $e){

        }
        return $rows;
    }

    /**
     * @return mixed
     * @throws RedisException
     */
    public function  count(){
        return $this->getRedisSet()->count();
    }

    /**
     * @param $page
     * @param int $pageSize
     * @param string $sorted
     * @return array
     * @throws RedisException
     */
    public   function  findByPage($page,$pageSize=20,$sorted ='asc'){
        $hash = $this->getRedisHash(null);
        $set =  $this->getRedisSet();
        $rows = array();
        $_datas= $set->getDataByPage($page,$pageSize,$sorted);
        if(!$_datas) return $rows;
        foreach($_datas as $_data){
            $rows[$_data] = $hash->getFieldByKey($_data);
            if(empty($rows[$_data])){
                $set->remove($_data);
            }
        }
        return $rows;
    }

    /**
     * @param $pk
     * @return bool
     * @throws RedisException
     */
    public   function  contains($pk){
        return $this->getRedisSet()->contains($this->getRedisKey($pk));
    }

    /**
     * @param SortedSet $redisSet
     */
    public function setRedisSet($redisSet){
        $this->redisSet = $redisSet;
    }

    /**
     * @return SortedSet
     * @throws RedisException
     */
    public function getRedisSet(){
        if ($this->redisSet === null) {
            $this->redisSet = new SortedSet($this->getRedisConnection());
        }
        $this->redisSet->setKey($this->getCommonKey());
        return $this->redisSet;
    }
}
