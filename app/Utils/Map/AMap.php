<?php


namespace App\Utils\Map;


use App\Consts\ErrorCode\RequestErrorCode;
use App\Exceptions\BusinessException;
use App\Utils\Dingding;
use App\Utils\Sign;
use GuzzleHttp\Client;

/**
 * 高德地图访问接口
 *
 * Class AMap
 * @package App\Utils\Map
 */
class AMap extends MapInterface
{


    public function __construct()
    {
        $this->type = 'amap';
        $this->uri = config('map.amap.url');
        $this->secret = config('map.amap.secret');
        $this->sign = config('map.amap.secret_sign');
        $this->client = new Client();
    }

    public function getAltitudeByAddress($address)
    {
        $data = [
            'address'=>$address,
            'key'=>$this->getSecret()
        ];
        $sig = Sign::getAmapSign($data,$this->sign);
        $queryString  = Sign::formatQueryParaMap($data);
        $url= $this->uri.'v3/geocode/geo?'.$queryString.'&sig='.$sig;
        try {
           $res = $this->client->get($url);
           $body = $res->getBody();
           $body = json_decode($body,true);
           if(!empty($body['count'])){
              $location = $body['geocodes'][0]['location'];
              $location = explode(',',$location);
              return $location;
           }
           if(!$body['status']){
               \Log::error("amap error message:{$body['info']} address:{$address}");
               Dingding::robot(new BusinessException($body['info'],RequestErrorCode::GAO_DE_FAILED));
           }
        }catch (\Exception $e){
            \Log::error("amap exception message:{$e->getMessage()} address:{$address}");
            Dingding::robot(new BusinessException($e->getMessage(),RequestErrorCode::GAO_DE_FAILED));
        }
        return [];

    }

    public function getAddressByAltitude($altitude)
    {
        $data = [
            'output'=> 'json',
            'location'=>$altitude,
            'key'=>$this->getSecret()
        ];
        $sig = Sign::getAmapSign($data,$this->sign);
        $queryString  = Sign::formatQueryParaMap($data);
        $url= $this->uri.'v3/geocode/regeo?'.$queryString.'&sig='.$sig;
        try {
            $res = $this->client->get($url);
            $body = $res->getBody();
            $body = json_decode($body,true);
            if(!empty($body['regeocode']['addressComponent'])){
                $addressComponent = $body['regeocode']['addressComponent'];
                $address['province'] = $addressComponent['province'] ?: '';
                $address['city'] = $addressComponent['city'] ?: '';
                $address['region'] = $addressComponent['district'] ?: '';
                $address['street'] = $addressComponent['township'] ?: '';
                $address['address_detail'] = '';
                $address['location'] = '';
                if(!empty($addressComponent['streetNumber']['street'])) {
                    $address['address_detail'] = $addressComponent['streetNumber']['street'] . $addressComponent['streetNumber']['direction'] . $addressComponent['streetNumber']['number'];
                    $address['location'] = $addressComponent['streetNumber']['location'] ?: '';
                }
                $address['gov_area_id'] = $addressComponent['towncode'] ?: '';
                $address['city_code'] = $addressComponent['adcode'] ?: '';
                $address['tip_address'] = '';
                if(empty( $body['regeocode']['formatted_address'])) {
                    $address['tip_address'] = $body['regeocode']['formatted_address'] ?: '';
                }
                return $address;
            }
            if(!$body['status']){
                \Log::error("amap altitude error message:{$body['info']} address:{$altitude}");
                Dingding::robot(new BusinessException($body['info'],RequestErrorCode::GAO_DE_FAILED));
            }
        }catch (\Exception $e){
            \Log::error("amap altitude exception message:{$e->getMessage()} address:{$altitude}");
             Dingding::robot(new BusinessException($e->getMessage(),RequestErrorCode::GAO_DE_FAILED));
        }
        return [];
    }

    public function getAltitudeByIp($ip)
    {
        $data = [
            'output'=> 'json',
            'ip'=>$ip,
            'key'=>$this->getSecret()
        ];
        $sig = Sign::getAmapSign($data,$this->sign);
        $queryString  = Sign::formatQueryParaMap($data);
        $url= $this->uri.'v3/ip?'.$queryString.'&sig='.$sig;
        try {
            $res = $this->client->get($url);
            $body = $res->getBody();
            $body = json_decode($body,true);
            if(!empty($body['rectangle'])){
                $body['rectangle'] = explode(';', $body['rectangle']);
                return $body;
            }
            if(!$body['status']){
                \Log::error("amap error message:{$body['info']} ip:{$ip}");
                Dingding::robot(new BusinessException($body['info'],RequestErrorCode::GAO_DE_FAILED));
            }
        }catch (\Exception $e){
            \Log::error("amap error message:{$e->getMessage()} ip:{$ip}");
            Dingding::robot($e);
        }
        return [];
    }
}
