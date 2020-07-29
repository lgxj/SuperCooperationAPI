<?php


namespace App\Utils\Map;


Interface YunTuInterface
{
    function createTable($tableName);
    function insert($tableId,$loctype,$lng,$lat,$address,$name,$businessNo, array $customField = []);
    function update ($tableId,$id,$loctype,$lng,$lat,$address,$name,$businessNo, array $customField = []);
    function delete($tableId,array $ids = []);
    function aroundSearch($tableId,$lng,$lat,$radius,$filter = '',$keywords = '',$sortRule = '',$page=1,$pageSize = 20);
}
