<?php


namespace App\Utils\Map;
use App\Consts\ErrorCode\RequestErrorCode;
use App\Exceptions\BusinessException;
use App\Utils\Sign;
use GuzzleHttp\Client;


class YunTu implements YunTuInterface
{
    const LOC_TYPE_COORDINATE = 1;
    const LOC_TYPE_ADDRESS = 2;
    const SORT_RULE_DISTANCE_ASC = '_distance:1';
    const SORT_RULE_DISTANCE_DESC = '_distance:0';

    protected $secret = '';
    protected $sign = '';
    protected $url = '';
    protected $client = null;

    public function __construct()
    {
        $this->secret = config('map.amap.secret');
        $this->sign = config('map.amap.secret_sign');
        $this->url = config('map.amap.yun_tu_url');
        $this->url = trim($this->url,'/').'/';
        $this->client = new Client();

    }

    /**
     * @param $tableName
     * @return mixed
     * @throws \Exception
     */
    public function createTable($tableName){
        $url = $this->url.'datamanage/table/create';
        try {
            $formData = [
                'key'=>$this->secret,
                'name'=>$tableName,
            ];
            $sign = Sign::getAmapSign($formData,$this->sign);
            $formData['sig'] = $sign;
            $res = $this->client->post($url,[
                'form_params'=> $formData,
                'verify'=>false
            ]);
            $body = $res->getBody();
            $body = json_decode($body,true);
            if($body['status'] != 1){
                \Log::error("yuntu create table message message:{$body['info']}");
                throw new BusinessException($body['info'],RequestErrorCode::YUN_TU_INFO);
            }
            return $body['tableid'];
        }catch (\Exception $e){
            \Log::error("yuntu create table message:{$e->getMessage()} address:{$tableName}");
            throw new BusinessException($e->getMessage(),RequestErrorCode::YUN_TU_FAILED);
        }
    }

    /**
     * @param string $tableId 云图表标识
     * @param int $loctype 存储维度
     * @param float $lng 经度
     * @param float $lat 纬度
     * @param string $address 经纬度对应的地址
     * @param string $name 名称
     * @param int $businessNo 业务唯一编号
     * @param array $customField
     * @return mixed
     * @throws BusinessException
     * @throws \Exception
     */
    public function insert($tableId,$loctype,$lng,$lat,$address,$name,$businessNo, array $customField = []){
        if(empty($tableId)){
            throw new BusinessException("云图参数错误",RequestErrorCode::YUN_TU_PARAM_ERROR);
        }
        $url = $this->url.'datamanage/data/create';
        try {
            $data = $customField;
            $data['_name'] = $name;
            $data['coordtype'] = 'autonavi';
            $data['business_no'] = $businessNo;
            $data['_address'] = $address;
            if($lng && $lat){
                $data['_location'] = "{$lng},{$lat}";
            }
            $formData = [
                'key'=>$this->secret,
                'tableid' => $tableId,
                'loctype' => $loctype,
                'data' => json_encode($data)
            ];
            $sign = Sign::getAmapSign($formData,$this->sign);
            $formData['sig'] = $sign;
            $res = $this->client->post($url,[
                'form_params'=> $formData,
                'verify'=>false
            ]);
            $body = $res->getBody();
            $body = json_decode($body,true);
            if($body['status'] != 1){
                \Log::error("yuntu insert data message message:{$body['info']}",$data);
                throw new BusinessException($body['info'],RequestErrorCode::YUN_TU_INFO);
            }
            return $body['_id'];
        }catch (\Exception $e){
            $args = func_get_args();
            \Log::error("yuntu insert data message:{$e->getMessage()} businessNo:{$businessNo},address:{$address}",$args);
            throw new BusinessException($e->getMessage(),RequestErrorCode::YUN_TU_FAILED);
        }
    }

    /**
     * @param string $tableId 云图表标识
     * @param int $id 数据标识
     * @param int $loctype 存储维度
     * @param float $lng 经度
     * @param float $lat 纬度
     * @param string $address 经纬度对应的地址
     * @param string $name 名称
     * @param int $businessNo 业务唯一编号
     * @param array $customField
     * @return mixed
     * @throws BusinessException
     * @throws \Exception
     */
    public function update ($tableId,$id,$loctype,$lng,$lat,$address,$name,$businessNo, array $customField = []){
        if(empty($tableId) || $id <= 0){
            throw new BusinessException("云图参数错误",RequestErrorCode::YUN_TU_PARAM_ERROR);
        }
        $url = $this->url.'datamanage/data/update';
        try {
            $data = $customField;
            $data['_name'] = $name;
            $data['coordtype'] = 'autonavi';
            $data['business_no'] = $businessNo;
            $data['_address'] = $address;
            $data['_id'] = $id;
            if($lng && $lat){
                $data['_location'] = "{$lng},{$lat}";
            }
            $formData = [
                'key'=>$this->secret,
                'tableid' => $tableId,
                'loctype' => $loctype,
                'data' => json_encode($data)
            ];
            $sign = Sign::getAmapSign($formData,$this->sign);
            $formData['sig'] = $sign;
            $res = $this->client->post($url,[
                'form_params'=> $formData,
                'verify'=>false
            ]);
            $body = $res->getBody();
            $body = json_decode($body,true);
            if($body['status'] != 1){
                \Log::error("yuntu update data message message:{$body['info']}",$data);
                throw new BusinessException($body['info'],RequestErrorCode::YUN_TU_INFO);
            }
            return $body['status'];
        }catch (\Exception $e){
            $args = func_get_args();
            \Log::error("yuntu update date message:{$e->getMessage()} businessNo:{$businessNo}",$args);
            throw new BusinessException($e->getMessage(),RequestErrorCode::YUN_TU_FAILED);
        }
    }


    /**
     * @param $tableId
     * @param array $ids
     * @return mixed
     * @throws BusinessException
     * @throws \Exception
     */
    public function delete($tableId,array $ids = []){
        if(empty($tableId) ||empty($ids)){
            throw new BusinessException("云图参数错误",RequestErrorCode::YUN_TU_PARAM_ERROR);
        }
        if(count($ids) > 50){
            throw new BusinessException("单次删除云图的数据不能超过50条",RequestErrorCode::YUN_TU_DELETE_LIMIT);
        }
        $url = $this->url.'datamanage/data/delete';
        try {
            $formData = [
                'key'=>$this->secret,
                'tableid'=>$tableId,
                'ids' => implode(',',$ids)
            ];
            $sign = Sign::getAmapSign($formData,$this->sign);
            $formData['sig'] = $sign;
            $res = $this->client->post($url,[
                'form_params'=> $formData,
                'verify'=>false
            ]);
            $body = $res->getBody();
            $body = json_decode($body,true);
            if($body['status'] != 1){
                \Log::error("yuntu delete data message message:{$body['info']}",['table_id'=>$tableId,'ids'=>$ids]);
                throw new BusinessException($body['info'],RequestErrorCode::YUN_TU_INFO);
            }
            return $body['success'];
        }catch (\Exception $e){
            \Log::error("yuntu delete data message:{$e->getMessage()} address:{$tableId}",['table_id'=>$tableId,'ids'=>$ids]);
            throw new BusinessException($e->getMessage(),RequestErrorCode::YUN_TU_FAILED);
        }
    }

    /**
     * @param $tableId
     * @param $lng
     * @param $lat
     * @param $radius
     * @param string $filter
     * @param string $keywords
     * @param string $sortRule
     * @param int $page
     * @param int $pageSize
     * @return array
     * @throws BusinessException
     */
    public function aroundSearch($tableId,$lng,$lat,$radius,$filter = '',$keywords = '',$sortRule = '',$page=1,$pageSize = 20){
        if(empty($tableId) || $radius < 0 ){
            throw new BusinessException("云图参数错误",RequestErrorCode::YUN_TU_PARAM_ERROR);
        }
        if($lng <= 0 || $lat <= 0){
            throw new BusinessException("中心点坐标必填",RequestErrorCode::YUN_TU_LNG_LAT_EMPTY);
        }
        if($radius > 50000){
            throw new BusinessException("查询半径不能大于5万米",RequestErrorCode::YUN_TU_QUERY_RADIOS_LIMIT);
        }
        if($radius <= 0){
            $radius = 50000;
        }
        if($pageSize >= 100){
            $pageSize = 100;
        }
        if(empty($sortRule)){
            $sortRule = '_distance:1';
        }
        $url = $this->url.'datasearch/around';
        $lng = round($lng,6);
        $lat = round($lat,6);
        try {
            if($page <= 0){
                $page = 1;
            }
            $query = [
                'key'=>$this->secret,
                'tableid'=>$tableId,
                'center' => "{$lng},{$lat}",
                'sortrule' => $sortRule,
                'radius' => $radius,
                'page' => $page,
                'limit' => $pageSize
            ];
            if($keywords){
                $query['keywords'] = $keywords;
            }
            if($filter){
                $query['filter'] = $filter;
            }
            $sign = Sign::getAmapSign($query,$this->sign);
            $query['sig'] = $sign;
            $res = $this->client->get($url,[
                'query'=> $query,
                'verify'=>false
            ]);
            $body = $res->getBody();
            $body = json_decode($body,true);
            if($body['status'] != 1){
                 unset($query['key']);
                \Log::error("yuntu around search data message message:{$body['info']}",$query);
                throw new BusinessException($body['info'],RequestErrorCode::YUN_TU_INFO);
            }
            return [$body['datas'],$body['count']];
        }catch (\Exception $e){
            $params = func_get_args();
            \Log::error("yuntu delete data message:{$e->getMessage()} address:{$tableId}",$params);
            throw new BusinessException($e->getMessage(),RequestErrorCode::YUN_TU_FAILED);
        }
    }

}
