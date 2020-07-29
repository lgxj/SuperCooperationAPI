<?php


namespace App\Services\Pool;


use App\Exceptions\BusinessException;
use App\Models\Pool\YunTuTable;
use App\Models\Pool\YunTuTableData;
use App\Services\ScService;
use App\Utils\Map\YunTu;

/**
 * 腾讯地点云管理
 *
 * 高德云图已下线
 * Class YunTuService
 * @package App\Services\Pool
 */
class YunTuService extends ScService
{

    const HELPER_TABLE_NAME = '帮手';
    const EMPLOYER_TABLE_NAME = '雇主';

    const BUSINESS_TYPE_EMPLOYER = 0;
    const BUSINESS_TYPE_HELPER = 1;

    public function createTable($tableName){
        $tableModel = $this->getTableModel();
        $amap = config('map.enable','amap');
        $tableName = $tableName . '-' . strtoupper(App()->environment());
        if($amap == 'amap') {
            $existTable = $tableModel->findByName($tableName);
            if ($existTable) {
                return $existTable->toArray();
            }
            $mapKey = config('map.amap.secret');
            $yunTuUtil = $this->getYunTuUtil();
            $tableId = $yunTuUtil->createTable($tableName);
        }elseif($amap == 'tencent'){
            $tableMap = [
                '雇主-LOCAL'=>'5e86d88925f3d52e16443b5b',
                '帮手-LOCAL'=>'5e86d8b331806b197a62c3c4',
                '帮手-PRODUCTION' => '5e86d8d36ffb171ee83d72eb',
                '雇主-PRODUCTION' => '5e86d8eac05f1003f40cec60'
            ];
            $mapKey = config('map.tencent.secret','');
            $tableId = $tableMap[$tableName];
            $existTable = $tableModel->findByTableId($tableId);
            if ($existTable) {
                return $existTable->toArray();
            }
        }else{
            $tableId = '';
        }
        if(empty($tableId)){
            throw new BusinessException("table id not exist");
        }
        $tableModel->table_name = $tableName;
        $tableModel->table_id = $tableId;
        $tableModel->amap_key = $mapKey;
        $tableModel->save();
        return $tableModel->toArray();
    }

    public function getHelperTableId(){
        return $this->createTable(self::HELPER_TABLE_NAME)['table_id'];
    }

    public function getEmployerTableId(){
        return $this->createTable(self::EMPLOYER_TABLE_NAME)['table_id'];
    }

    public function getRealTable($type = self::EMPLOYER_TABLE_NAME){
         if($type == self::EMPLOYER_TABLE_NAME){
             return $this->getEmployerTableId();
         }else{
             return $this->getHelperTableId();
         }
    }

    public function addYuTuTableData($businessType,$tableId,$tableDataId,$businessNo){
        $model = new YunTuTableData();
        $exist = $this->findYunTuTableDataByBusinessNo($tableId,$businessNo);
        if($exist){
            return $exist->toArray();
        }
        $model->business_type = $businessType;
        $model->table_id = $tableId;
        $model->table_business_id = $tableDataId;
        $model->business_no = $businessNo;
        $model->save();
        return $model->toArray();
    }

    public function findYunTuTableDataByBusinessNo($tableId,$businessNo){
        return  YunTuTableData::where(['business_no'=>$businessNo,'table_id'=>$tableId])->first();
    }

    public function findYunTuTableDataByBusinessNos($tableId,array $businessNos){
        if(empty($businessNos)){
            return [];
        }
        return  YunTuTableData::where(['table_id'=>$tableId])->whereIn('business_no',$businessNos)->get();
    }

    public function delYunTuTableDataByBusinessNos($tableId,array $businessNos){
        if(empty($businessNos)){
            return [];
        }
        return  YunTuTableData::where(['table_id'=>$tableId])->whereIn('business_no',$businessNos)->delete();
    }
    protected function getYunTuUtil(){
        return new YunTu();
    }

    protected function getTableModel(){
        return new YunTuTable();
    }
}
