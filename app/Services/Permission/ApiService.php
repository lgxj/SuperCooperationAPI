<?php

namespace App\Services\Permission;

use App\Bridges\Permission\AdminLogBridge;
use App\Consts\PermissionConst;
use App\Exceptions\BusinessException;
use App\Models\Permission\Api;
use App\Models\Permission\Cache\ApiCache;
use App\Utils\NoSql\Redis\RedisException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ApiService extends BasePermissionService
{
    /**
     * @var Api
     */
    protected $apiModel;

    /**
     * @var AdminLogService
     */
    protected $adminLogBridge;

    public function __construct()
    {
        $this->apiModel = new Api();
        $this->adminLogBridge = new AdminLogBridge(new AdminLogService());
    }

    /**
     * 添加
     * @param int $group_id 所属分组ID
     * @param string $name 名称
     * @param string $path 地址
     * @param string $method 请求方式
     * @param string $status 状态
     * @param int $is_dev 是否开发中
     * @param int $need_power 是否需要相关权限
     * @param int $sort 排序
     * @param string $remark 备注
     * @param int $systemId
     * @return array
     * @throws BusinessException
     */
    public function add($group_id, $name, $path, $method, $status, $is_dev, $need_power, $sort, $remark,$systemId = PermissionConst::SYSTEM_MANAGE)
    {
        $data = [
            'group_id' => $group_id,
            'name' => $name,
            'path' => $path,
            'method' => $method,
            'status' => $status,
            'is_div' => $is_dev,
            'need_power' => $need_power,
            'sort' => $sort,
            'remark' => $remark,
            'system_id' => $systemId
        ];
        $validate = \Validator::make($data, [
            'name' => 'required',
            'group_id' => 'required',
            'path' => 'required',
            'method' => 'required'
        ], [
            'name.required' => 'API名不能为空',
            'group_id.unique' => '请选择所属分组',
            'path.required' => '地址不能为空',
            'method.unique' => '请选择请求方式',
            'system_id.required' => '系统ID不存在'

        ]);
        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first());
        }

        try {
            // 添加
            $this->apiModel->add($group_id, $name, $path, $method, $sort, $status, $is_dev, $need_power, $remark,$systemId);
            $id = $this->apiModel->api_id;

            return ['api_id' => $id];
        } catch (\Exception $e) {
            \Log::error('添加API失败:' . json_encode($data, 320) . PHP_EOL . ' message: ' . $e->getMessage());
            throw new BusinessException('添加失败');
        }
    }

    /**
     * 编辑
     * @param int $id
     * @param array $data
     * @return bool
     * @throws BusinessException
     */
    public function edit(int $id, array $data)
    {
        $validate = \Validator::make($data, [
            'name' => 'required',
            'group_id' => 'required',
            'path' => 'required',
            'method' => 'required',
            'system_id' => 'required|integer'
        ], [
            'name.required' => 'API名不能为空',
            'group_id.unique' => '请选择所属分组',
            'path.required' => '地址不能为空',
            'method.unique' => '请选择请求方式',
            'system_id.required' => '系统ID不存在'
        ]);
        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first());
        }

        $api = $this->apiModel->getById($id);
        if (!$api) {
            throw new BusinessException('API信息未找到');
        }

        try {
            $this->getApiCache($api['system_id'])->del($api);
            $api->edit($id, $data);

            return true;
        } catch (\Exception $e) {
            \Log::error('编辑API失败:' . json_encode($data, 320) . PHP_EOL . ' message: ' . $e->getMessage());
            throw new BusinessException('编辑失败');
        }
    }

    /**
     * 列表
     * @param array $filter
     * @param array $columns
     * @param int $pageSize
     * @param string $orderColumn
     * @param string $direction
     * @return LengthAwarePaginator
     */
    public function getList($filter = [], $columns = ['*'], $pageSize = 10, $orderColumn = 'api_id', $direction = 'desc')
    {
        $model = $this->apiModel
            ->where('system_id', $filter['system_id'] ?? 0)
            ->when(!empty($filter['group_id']), function ($query) use ($filter) {
                $query->where('group_id', $filter['group_id']);
            })
            ->when(isset($filter['status']) && $filter['status'] !== '', function ($query) use ($filter) {
                $query->where('status', $filter['status']);
            })
            ->when(isset($filter['is_dev']) && $filter['is_dev'] !== '', function ($query) use ($filter) {
                $query->where('is_dev', $filter['is_dev']);
            })
            ->when(isset($filter['system_id']) && $filter['system_id'] > 0, function ($query) use ($filter) {
                $query->where('system_id', $filter['system_id']);
            })
            ->when(!empty($filter['keyword']), function ($query) use ($filter) {
                $query->where('name', 'LIKE', '%' . $filter['keyword'] . '%')->whereOr('path', 'LIKE', '%' . $filter['keyword'] . '%');
            });

        return $model->select($columns)->orderBy($orderColumn, $direction)->paginate($pageSize);
    }

    /**
     * 根据地址查询接口
     * @param $systemId
     * @param $path
     * @param $method
     * @return int
     * @throws RedisException
     */
    public function getByPath($systemId,$path,$method)
    {
        if($systemId <=0 || empty($path) || empty($method)){
            return 0;
        }
        $isCache = true;
        try{
            $api =  $this->getApiCache($systemId)->getByPath($path,$method);
            if($api){
                return $api['api_id'];
            }
        }catch (\Exception $e){
            $isCache = false;
        }
        $api = $this->apiModel->where(['path'=>$path,'method'=>$method,'system_id'=>$systemId])->first();
        if(empty($api)){
            return 0;
        }
        if($isCache){
            $this->getApiCache($api['system_id'])->save($api);
        }
        return $api->toArray()['api_id'];
    }

    /**
     * 解冻管理员
     * @param int $id
     * @return bool
     * @throws BusinessException
     */
    public function del(int $id)
    {
        if($id <= 0){
            return false;
        }
        try {
            $api = $this->apiModel->find($id);
            if(empty($api)){
                return  true;
            }
            // 删除缓存
            $this->getApiCache($api['system_id'])->del($api);
            // 删除与资源关联
            $api->resources()->detach();
            $api->delete();

            return true;
        } catch (\Exception $e) {
            \Log::error('删除API失败' . $id. '失败，message: ' . $e->getMessage());
            throw new BusinessException('删除失败');
        }
    }

    protected function getApiCache($systemId){
        return (new ApiCache($systemId));
    }
}
