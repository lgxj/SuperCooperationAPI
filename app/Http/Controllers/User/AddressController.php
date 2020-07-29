<?php


namespace App\Http\Controllers\User;


use App\Http\Controllers\Controller;
use App\Services\User\AddressService;
use App\Utils\Map\AMap;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    protected $addressService;

    public function __construct(AddressService $addressService)
    {
        $this->addressService = $addressService;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function add(Request $request){
        $address = $request->all();
        $address['user_id'] = $this->getUserId();
        $address = $this->addressService->add($address);
        return success($address);
     }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
     public function update(Request $request){
         $address = $request->all();
         $address['user_id'] = $this->getUserId();
         $address = $this->addressService->update($address);
         return success($address);
     }


     public function remove(Request $request){
         $address = $request->input();
         $flag = $this->addressService->remove($this->getUserId(),$address['id']);
         return success(['flag'=>$flag]);
     }

     public function setDefault(Request $request){
         $address = $request->input();
         $flag = $this->addressService->setDefault($this->getUserId(),$address['id']);
         return success(['flag'=>$flag]);
     }

     public function find(Request $request){
         $address = $request->input();
         $address = $this->addressService->find($this->getUserId(),$address['id']);
         return success($address);
     }

     public function findAll(Request $request){
         $address = $this->addressService->findAll($this->getUserId());
         return success($address);
     }


}
