<?php


namespace App\Services\Pool;


use App\Exceptions\BusinessException;
use App\Models\Pool\Address;
use App\Services\ScService;
use App\Utils\Recursion;

/**
 * 全国地址库管理
 *
 * Class AddressService
 * @package App\Services\Pool
 */
class AddressService extends ScService
{

    public function getParentListByGovCode(int $govAreaId) : array
    {
        static $list = [];
        if($govAreaId <= 0){
            return [];
        }
        $address = Address::where('gov_area_id',$govAreaId)->first();
        if($address){
            $list[] = $address->toArray();
            $id = $address['parent_id'];
            if($id > 0){
                $this->getParentListByGovCode($id);
            }
        }
        return $list;
    }

   public function provinces() : array
   {
       return Address::where('parent_id',0)->get()->toArray();
   }


   public function child(int $govAreaId) : array
   {
       if($govAreaId <= 0){
           return [];
       }
       return Address::where('parent_id',$govAreaId)->get()->toArray();
   }

   public function tree(int $level) : array
   {
        $data = Address::where('level' ,'<=',$level)->get();
        $tmp = [];
        foreach ($data as $value){
            $tmp[$value['address_id']] = $value;
        }
        $tree = Recursion::recursionTree($tmp,'address_id');
        return $tree;
    }

    public function getByGovAreaId(int $govAreaId):array
    {
        if($govAreaId <= 0){
            return [];
        }
        $data = Address::where('gov_area_id',$govAreaId)->first();
        return $data ? $data->toArray() : [];
    }
    public function getById(int $id):array
    {
        if($id <= 0){
            return [];
        }
        $data = Address::where('address_id',$id)->first();
        return $data ? $data->toArray() : [];
    }

    public function getAreaByCode($code){
        $code = $this->strpad($code);
        $list = $this->getParentListByGovCode($code);
        if(empty($list)){
            return [];
        }
        $list = collect($list)->keyBy('gov_area_id')->toArray();
        $province = $list[$this->strpad(substr($code, 0, 2))] ?? [];
        $city = $list[$this->strpad(substr($code, 0, 4))] ?? [];
        $region = $list[$this->strpad(substr($code, 0, 6))] ?? [];
        $street = $list[$this->strpad(substr($code, 0, 9))] ?? [];
        $return = [];
        if($province){
            $return['province'] = [
                'gov_area_id'=>$province['gov_area_id'],
                'parent_id'=>$province['parent_id'],
                'name'=>$province['name'],
                'address_id' => $province['address_id']
            ];
        }
        if($city && $city != $province){
            $return['city'] = [
                'gov_area_id'=>$city['gov_area_id'],
                'parent_id'=>$city['parent_id'],
                'name'=>$city['name'],
                'address_id' => $city['address_id']
            ];
        }
        if($region && $region != $city){
            $return['region'] = [
                'gov_area_id'=>$region['gov_area_id'],
                'parent_id'=>$region['parent_id'],
                'name'=>$region['name'],
                'address_id' => $region['address_id']
            ];
        }
        if($street && $street != $region){
            $return['street'] = [
                'gov_area_id'=>$street['gov_area_id'],
                'parent_id'=>$street['parent_id'],
                'name'=>$street['name'],
                'address_id' => $street['address_id']
            ];
        }
        return $return;

    }

    public function calcAltitude($code){
        $list = $this->getAreaByCode($code);
        $data['province'] = $list['province']['name'];
        $data['city'] = $list['city']['name'];
        $data['region'] = $list['region']['name'] ?? '';
        $data['street'] = $list['street']['street'] ?? '';
        $updateAddressId = $list['street']['address_id'] ?? 0;
        if($updateAddressId <= 0){
            $updateAddressId = $list['region']['address_id'] ?? 0;
        }
        if($updateAddressId <= 0){
            $updateAddressId = $list['city']['address_id'] ?? 0;
        }
        $address = Address::where('address_id',$updateAddressId)->first();
        $altitude = calcAltitude($data);
        if($altitude && $updateAddressId > 0 && empty($address['lng'])){
            $address->lng = $altitude[0];
            $address->lat = $altitude[1];
            $address->save();
        }
        return $address->toArray();
    }

    public function getByName($provinceName,$cityName = '',$regionName = ''){
        $return = [
            'province' => '',
            'province_gov_id' => 0,
            'city' => '',
            'city_gov_id' => 0,
            'region' => '',
            'region_gov_id' => 0,
        ];
        if(empty($provinceName)){
            return $return;
        }
        $province = Address::where(['name'=>$provinceName,'parent_id'=>0])->first();
        if(empty($province)){
            return $return;
        }
        $return['province'] = $province['name'];
        $return['province_gov_id'] = $province['gov_area_id'];
        if(empty($cityName)){
            return $return;
        }
        $city = Address::where(['name'=>$cityName,'parent_id'=>$province['gov_area_id']])->first();
        if(empty($city)){
            return $return;
        }
        $return['city'] = $city['name'];
        $return['city_gov_id'] = $city['gov_area_id'];
        if(empty($regionName)){
            return $return;
        }
        $region = Address::where(['name'=>$regionName,'parent_id'=>$city['gov_area_id']])->first();
        if(empty($region)){
            return $return;
        }
        $return['region'] = $region['name'];
        $return['region_gov_id'] = $region['gov_area_id'];
        return $return;
    }

    protected function strpad($code){
        return str_pad($code,12,0,STR_PAD_RIGHT );
    }
}
