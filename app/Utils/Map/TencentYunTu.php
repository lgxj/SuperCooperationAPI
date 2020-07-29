<?php


namespace App\Utils\Map;
use App\Consts\ErrorCode\RequestErrorCode;
use App\Exceptions\BusinessException;
use App\Utils\Sign;
use GuzzleHttp\Client;

/**
 * 腾讯地点云接口访问
 *
 * Class TencentYunTu
 * @package App\Utils\Map
 */
class TencentYunTu implements YunTuInterface
{
    protected $secret = '';
    protected $sign = '';
    protected $url = '';
    protected $client = null;

    public function __construct()
    {
        $this->secret = config('map.tencent.secret');
        $this->sign = config('map.tencent.secret_sign');
        $this->url = config('map.tencent.yun_tu_url');
        $this->url = trim($this->url,'/').'/';
        $this->client = new Client();

    }

    /**
     * @param $tableName
     * @return mixed
     * @throws \Exception
     */
    public function createTable($tableName){
        \Log::error("tentcent yuntu not support");
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
        $url = $this->url.'place_cloud/data/create';
        try {
            $lng = round($lng,6);
            $lat = round($lat,6);
            $customField['business_no'] = $businessNo;
            $customField['created_at'] = time();
            $data['title'] = $name;
            $data['ud_id'] = "{$businessNo}";
            $data['address'] = $address;
            if($lng && $lat){
                $data['location'] = ['lng'=>$lng,'lat'=>$lat];
            }
            $data['x'] = $customField;
            $formData = [
                'key'=>$this->secret,
                'table_id' => $tableId,
                'data' => [$data]
            ];
          //  $sign = Sign::getAmapSign($formData,$this->sign);
          //  $formData['sig'] = $sign;return;
            $res = $this->client->post($url,[
                'json'=> $formData,
                'verify'=>false
            ]);
            $body = $res->getBody();
            $body = json_decode($body,true);
            if($body['status'] != 0){
                \Log::error("tencent yuntu insert data message message:{$body['message']}",$data);
                throw new BusinessException($body['message'],RequestErrorCode::YUN_TU_INFO);
            }
            if( isset($body['result']['failure'][0]['message'])){
                \Log::error("tencent yuntu insert data message message:{$body['message']}",$data);
                throw new BusinessException($body['result']['failure'][0]['message'],RequestErrorCode::YUN_TU_INFO);
            }
            return $body['result']['success'][0]['id'] ?? '';
        }catch (\Exception $e){
            $args = func_get_args();
            \Log::error("tencent yuntu insert data message:{$e->getMessage()} businessNo:{$businessNo},address:{$address}",$args);
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
        $url = $this->url.'place_cloud/data/update';
        try {
            $lng = round($lng,6);
            $lat = round($lat,6);
            $customField['business_no'] = $businessNo;
            $data['title'] = $name;
            $data['ud_id'] = "{$businessNo}";
            $data['address'] = $address;
            if($lng && $lat){
                $data['location'] = ['lng'=>$lng,'lat'=>$lat];
            }
            $data['x'] = $customField;
            $formData = [
                'key'=>$this->secret,
                'table_id' => $tableId,
                'data' => $data,
                'filter' => "id=\"{$id}\""
            ];
            //$sign = Sign::getAmapSign($formData,$this->sign);
            //$formData['sig'] = $sign;
            $res = $this->client->post($url,[
                'json'=> $formData,
                'verify'=>false
            ]);
            $body = $res->getBody();
            $body = json_decode($body,true);
            if($body['status'] != 0){
                \Log::error("tentcent yuntu update data message message:{$body['message']}",$data);
                throw new BusinessException($body['message'],RequestErrorCode::YUN_TU_INFO);
            }
            return $body['status'];
        }catch (\Exception $e){
            $args = func_get_args();
            \Log::error("tentcent yuntu update date message:{$e->getMessage()} businessNo:{$businessNo}",$args);
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
            throw new BusinessException("腾讯云图参数错误",RequestErrorCode::YUN_TU_PARAM_ERROR);
        }
        if(count($ids) > 50){
            throw new BusinessException("单次删除腾讯云图的数据不能超过50条",RequestErrorCode::YUN_TU_DELETE_LIMIT);
        }
        $url = $this->url.'place_cloud/data/delete';
        try {
            $idString = '';
            foreach ($ids as $id){
                $idString .=  $idString ? ','."{$id}" : "{$id}";
            }
            $formData = [
                'key'=>$this->secret,
                'table_id' => $tableId,
                'filter' => "id=\"{$ids[0]}\""
            ];
            //$sign = Sign::getAmapSign($formData,$this->sign);
            //$formData['sig'] = $sign;
            $res = $this->client->post($url,[
                'json'=> $formData,
                'verify'=>false
            ]);
            $body = $res->getBody();
            $body = json_decode($body,true);
            if($body['status'] != 0){
                \Log::error("tencent yuntu delete data message message:{$body['message']}",['table_id'=>$tableId,'ids'=>$ids]);
                throw new BusinessException($body['message'],RequestErrorCode::YUN_TU_INFO);
            }
            return $body['status'];
        }catch (\Exception $e){
            \Log::error("tencent yuntu delete data message:{$e->getMessage()} address:{$tableId}",['table_id'=>$tableId,'ids'=>$ids]);
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
        $url = $this->url.'place_cloud/search/nearby';
        $lng = round($lng,6);
        $lat = round($lat,6);
        if(empty($sortRule)){
            $sortRule = "distance({$lat},{$lng})";
        }
        try {
            if($page <= 0){
                $page = 1;
            }
            $query = [
                'key'=>$this->secret,
                'table_id'=>$tableId,
                'location' => "{$lat},{$lng}",
                'orderby' => $sortRule,
                'radius' => $radius,
                'page_index' => $page,
                'page_size' => $pageSize
            ];
            if($keywords){
                $query['keyword'] = $keywords;
            }
            if($filter){
                $query['filter'] = $filter;
            }
            //$sign = Sign::getAmapSign($query,$this->sign);
            //$query['sig'] = $sign;
            $res = $this->client->get($url,[
                'query'=> $query,
                'verify'=>false
            ]);
            $body = $res->getBody();
            $body = json_decode($body,true);
            if($body['status'] != 0){
                 unset($query['key']);
                \Log::error("tencent yuntu around search data message message:{$body['message']}",$query);
                throw new BusinessException($body['message'],RequestErrorCode::YUN_TU_INFO);
            }
            $list = $body['result']['data'] ?? [];
            foreach ($list as $key=>$value){
                $location = $value['location'];
                $list[$key] = array_merge($list[$key],$value['x']);
                $list[$key]['_location'] = "{$location['lng']},{$location['lat']}";
                $list[$key]['_distance'] = $value['_distance'] ?? 0;
                $list[$key]['_distance'] = round($list[$key]['_distance']);
                $list[$key]['_id'] = $value['id'];
                $list[$key]['_name'] = $value['title'];
                unset($list[$key]['x'],$list[$key]['location']);
            }
            return [$list,$body['result']['count']];
        }catch (\Exception $e){
            $params = func_get_args();
            \Log::error("tencent yuntu delete data message:{$e->getMessage()} address:{$tableId}",$params);
            throw new BusinessException($e->getMessage(),RequestErrorCode::YUN_TU_FAILED);
        }
    }

}
