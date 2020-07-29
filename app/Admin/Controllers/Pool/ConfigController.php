<?php


namespace App\Admin\Controllers\Pool;


use App\Admin\Controllers\ScController;
use App\Bridges\Pool\GlobalConfigBridge;
use App\Services\Pool\ConfigService;
use Illuminate\Http\Request;

class ConfigController extends ScController
{
    /**
     * @var ConfigService
     */
    protected $configBridge;

    public function __construct(GlobalConfigBridge $configBridge)
    {
        $this->configBridge = $configBridge;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     * @throws \OSS\Core\OssException
     */
    public function add(Request $request){
        $config = $request->all();
        $config = $this->configBridge->addConfig($config);
        return success($config);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     * @throws \OSS\Core\OssException
     */
    public function update(Request $request){
        $config = $request->all();
        $config = $this->configBridge->updateConfig($config);
        return success($config);
    }

    /**
     * 修改值
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function setValue(Request $request)
    {
        $config = $request->input();
        $flag = $this->configBridge->setValue($config['config_key'], $config['config_value']);
        if ($flag !== false) {
            return success();
        }
        return out(1, '修改失败', false);
    }

    public function remove(Request $request){
        $config = $request->input();
        $flag = $this->configBridge->deleteByKey($config['config_key']);
        return success(['flag'=>$flag]);
    }

    public function find(Request $request){
        $config = $request->input();
        $config = $this->configBridge->getByKey($config['config_key']);
        return success($config);
    }

    public function findAll()
    {
        $list = $this->configBridge->getList();
        foreach ($list as &$item) {
            if ($item['config_type'] == 'checkbox') {
                $item['config_value'] = json_decode($item['config_value'], true);
            }
        }
        return success($list);
    }
}
