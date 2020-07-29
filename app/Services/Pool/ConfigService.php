<?php
namespace App\Services\Pool;

use App\Consts\ErrorCode\PoolErrorCode;
use App\Exceptions\BusinessException;
use App\Models\Pool\GlobalConfig;
use App\Services\ScService;

/**
 * 配置管理
 *
 * Class ConfigService
 * @package App\Services\Pool
 */
class ConfigService extends ScService
{

    public $timestamps = false;



    public function __construct()
    {
    }

    /**
     * 增加
     * @param $data
     * @return array
     * @throws BusinessException
     */
    public function addConfig($data)
    {
        $config = $this->getBykey($data['config_key']);
        if ($config) {
            throw new BusinessException("{$data['config_key']} 配置项已存在",PoolErrorCode::CONFIG_KEY_EXIST);
        }

        if ($data['config_type'] == 'checkbox') {
            $data['config_value'] = json_encode($data['config_value'], 320);
        }

        $globalConfig = new GlobalConfig();
        $globalConfig->config_key = $data['config_key'];
        $globalConfig->config_value = $data['config_value'];
        $globalConfig->config_name = $data['config_name'];
        $globalConfig->config_type = $data['config_type'];
        $globalConfig->config_group = $data['config_group'];
        $globalConfig->config_type = $data['config_type'];
        $globalConfig->config_extra = $data['config_extra'];
        $globalConfig->config_desc = $data['config_desc'];
        $globalConfig->save();
        return $globalConfig->toArray();
    }


    /**
     * 编辑
     * @param $data
     * @return array
     * @throws BusinessException
     */
    public function updateConfig($data)
    {
        $config = GlobalConfig::where('config_key', $data['config_key'])->first();
        if (empty($config)) {
            throw new BusinessException("{$data['config_key']} 配置项不存在",PoolErrorCode::CONFIG_KEY_NOT_EXIST);
        }

        if ($data['config_type'] == 'checkbox') {
            $data['config_value'] = json_encode($data['config_value'], 320);
        }

        $config->config_key = $data['config_key'];
        $config->config_value = $data['config_value'];
        $config->config_name = $data['config_name'];
        $config->config_type = $data['config_type'];
        $config->config_group = $data['config_group'];
        $config->config_type = $data['config_type'];
        $config->config_extra = $data['config_extra'];
        $config->config_desc = $data['config_desc'];
        $config->save();
        return $config->toArray();
    }

    /**
     * 修改值
     * @param $key
     * @param $value
     * @return bool
     * @throws BusinessException
     */
    public function setValue($key, $value)
    {
        $config = GlobalConfig::where('config_key', $key)->first();
        if (empty($config)) {
            throw new BusinessException("{$key} 配置项不存在",PoolErrorCode::CONFIG_KEY_NOT_EXIST);
        }

        if ($config['config_type'] == 'checkbox') {
            $value = json_encode($value, 320);
        }

        $config->config_value = $value;
        $config->save();
        return true;
    }

    public function deleteByKey($key)
    {
        if (GlobalConfig::where('config_key', $key)->delete()) {
            return true;
        } else {
            throw new BusinessException('删除失败',PoolErrorCode::CONFIG_KEY_DELETE_FAILED);
        }
    }


    public function getByKey($key)
    {
        $data = GlobalConfig::where('config_key', $key)->first();
        if (empty($data)) {
            return [];
        }
        return $data->toArray();
    }

    public function getValueByKey($key,$default = '')
    {
        $data = GlobalConfig::where('config_key', $key)->first();
        if (empty($data)) {
            return $default;
        }
        return $data->config_value;
    }

    public function getByKeys(array $keys)
    {
        $data = GlobalConfig::whereIn('config_key', $keys)->get();
        if (empty($data)) {
            return [];
        }
        return $data->pluck('config_value', 'config_key')->toArray();
    }

    public function findAll($key = null, $value = null, $pageSize = 20)
    {
        $paginate = GlobalConfig::when($key, function ($query) use ($key) {
            $query->where('config_key', 'like', "%{$key}%");
        })
            ->when($value, function ($query) use ($value) {
                $query->where('config_value', 'like', "%{$value}%");
            })
            ->paginate($pageSize);
        if (empty($paginate)) {
            return [];
        }
        $data = collect($paginate->items())->pluck('config_value', 'config_key')->toArray();
        return ['items' => $data, 'pageSize' => $pageSize, 'total' => $paginate->total()];
    }

    public function getList()
    {
        return GlobalConfig::all()->toArray();
    }
}
