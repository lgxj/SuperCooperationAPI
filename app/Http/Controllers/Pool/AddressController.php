<?php


namespace App\Http\Controllers\Pool;


use App\Http\Controllers\Controller;
use App\Services\Pool\AddressService;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    protected $addressService;


    public function __construct(AddressService $addressService)
    {
        $this->addressService = $addressService;
    }

    public function provinces(Request $request){
        $provinces = $this->addressService->provinces();
        return success($provinces);
    }

    public function associateNextLevel(Request $request){
        $areaGovId = $request->get('area_gov_id',0);
        $childs = $this->addressService->child($areaGovId);
        return success($childs);
    }

    public function allParent(Request $request){
        $areaGovId = $request->get('area_gov_id',0);
        $parent = $this->addressService->getParentListByGovCode($areaGovId);
        return success($parent);
    }

    public function getAreaByCode(Request $request){
        $areaGovId = $request->get('code',0);
        $parent = $this->addressService->getAreaByCode($areaGovId);
        return success($parent);
    }

    public function calcAltitude(Request $request){
        $areaGovId = $request->get('area_gov_id',0);
        $address = $this->addressService->calcAltitude($areaGovId);
        return success($address);
    }

    public function getByName(Request $request){
        $province = $request->get('province','');
        $city = $request->get('city','');
        $region = $request->get('region','');
        $address = $this->addressService->getByName($province,$city,$region);
        return success($address);
    }
}
