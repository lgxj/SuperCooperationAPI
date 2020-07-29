<?php


namespace App\Models\Pool;


class YunTuTable extends BasePool
{
    public $timestamps = false;

    protected $table = 'yuntu_table';

    protected $primaryKey = 'yuntu_id';



    public function findByName($tableName){
        return $this->where('table_name',$tableName)->first();
    }

    public function findByTableId($tableId){
        return $this->where('table_id',$tableId)->first();
    }
}
