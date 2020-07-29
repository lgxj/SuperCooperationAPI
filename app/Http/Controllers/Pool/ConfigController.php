<?php


namespace App\Http\Controllers\Pool;


use App\Http\Controllers\Controller;
use App\Services\Pool\ConfigService;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    protected $configService;


    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    public function get(Request $request){
        $value = $this->configService->getByKey($request->get('key'));
        return success($value);
    }


    public function gets(Request $request){
        $list = $this->configService->getBykeys(explode(',',$request->get('key')));
        return success($list);
    }

}
