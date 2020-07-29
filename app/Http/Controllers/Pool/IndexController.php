<?php


namespace App\Http\Controllers\Pool;


use App\Http\Controllers\Controller;
use App\Services\Pool\YunTuService;
use Illuminate\Http\Request;

class IndexController extends Controller
{

    public function map(Request $request){
        $mapType = $request->get('map_type',YunTuService::EMPLOYER_TABLE_NAME);
        $yunTuService = $this->getYuTuService();
        $data['table_id'] = $yunTuService->getRealTable($mapType);
        $data['distance'] = 10;
        $data['is_certification'] = $this->getUserLoginField('is_certification',0);
        return success($data);
    }

    protected function getYuTuService(){
        return new YunTuService();
    }
}
