<?php
/**
 * Created by PhpStorm.
 * User: suqian
 * Date: 14-9-16
 * Time: 下午4:17
 */

namespace App\Utils\NoSql\Redis\Table;

use App\Utils\NoSql\Redis\Store;

class HashTable extends Store {

    public function  insert($pk, array $attribute)
    {
        // TODO: Implement insert() method.
    }

    public function  count()
    {
        // TODO: Implement count() method.
    }

    public function  save($pk, array $attribute)
    {
        // TODO: Implement save() method.
    }

    public function  delete($pk)
    {
        // TODO: Implement delete() method.
    }

    public function  update($pk, array $attribute)
    {
        // TODO: Implement update() method.
    }

    public function  findByPk($pk)
    {
        // TODO: Implement findByPk() method.
    }

    public function  findAll()
    {
        // TODO: Implement findAll() method.
    }

    public function  deleteAll()
    {
        // TODO: Implement deleteAll() method.
    }

    public   function  findByPage($page,$pageSize=20,$sorted ='asc'){

    }

    public   function  contains($pk){

    }


}
