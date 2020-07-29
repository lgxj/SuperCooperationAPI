<?php

namespace App\Services\Permission;

use App\Bridges\Permission\AdminLogBridge;
use App\Consts\DBConnection;
use App\Consts\PermissionConst;
use App\Exceptions\BusinessException;
use App\Models\Permission\Resource;
use App\Models\Permission\ResourceApi;
use App\Models\Permission\RoleResource;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class ResourceService extends BasePermissionService
{
    /**
     * @var Resource
     */
    protected $resourceModel;

    /**
     * @var AdminLogService
     */
    protected $adminLogBridge;

    public function __construct()
    {
        $this->resourceModel = new Resource();
        $this->adminLogBridge = new AdminLogBridge(new AdminLogService());
    }

    /**
     * @param int $fid 上级ID
     * @param int $type 类型
     * @param string $name 名称
     * @param string $code 编码
     * @param int $sort 排序
     * @param int $status 状态
     * @param int $is_dev 是否开发中
     * @param string $remark 备注
     * @param array $apiIds API
     * @param int $systemId 所属系统ID
     * @return array
     * @throws BusinessException
     */
    public function add($fid, $type, $name, $code, $sort, $status, $is_dev, $remark, $apiIds, $systemId)
    {
        $data = [
            'name' => $name,
            'code' => $code,
            'fid' => $fid,
            'type' => $type,
            'sort' => $sort,
            'status' => $status,
            'is_dev' => $is_dev,
            'remark' => $remark
        ];
        $validate = \Validator::make($data, [
            'name' => 'required',
            'code' => ['required', Rule::unique('sc_permission.resource')->where(function ($query) use ($systemId) {
                $query->where('system_id', $systemId);
            })],
            'type' => 'required'
        ], [
            'name.required' => '资源名不能为空',
            'code.required' => '资源编码不能为空',
            'code.unique' => '资源编码已存在',
            'type.required' => '请选择类型'
        ]);
        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first());
        }
        $connection = DBConnection::getPermissionConnection();
        try {
            $connection->beginTransaction();
            // 添加
            $this->resourceModel->add($fid, $name, $type, $code, $sort, $status, $is_dev, $remark, $systemId);
            $id = $this->resourceModel->getKeyId();
            // 保存接口关联
            $this->resourceModel->apis()->attach($apiIds);
            $connection->commit();
            return ['resource_id' => $id];
        } catch (\Exception $e) {
            $connection->rollBack();
            \Log::error('添加资源失败:' . json_encode($data, 320) . PHP_EOL . ' message: ' . $e->getMessage());
            throw new BusinessException('添加失败');
        }
    }

    /**
     * 编辑
     * @param int $id
     * @param array $data
     * @param array $apiIds
     * @param int $systemId
     * @return bool
     * @throws BusinessException
     */
    public function edit(int $id, array $data, array $apiIds, int $systemId)
    {
        $validate = \Validator::make($data, [
            'name' => 'required',
            'code' => ['required', Rule::unique('sc_permission.resource')->where(function (Builder $query) use ($systemId, $id) {
                $query->where('system_id', $systemId)->where('resource_id', '<>', $id);
            })],
            'fid' => 'required',
        ], [
            'name.required' => '资源名不能为空',
            'code.required' => '资源编码不能为空',
            'code.unique' => '资源编码已存在',
            'fid.required' => '请选择上级'
        ]);
        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first());
        }

        $resource = $this->resourceModel->getById($id);
        if (!$resource) {
            throw new BusinessException('角色信息未找到');
        }

        try {
            // 保存
            $resource->edit($id, $data);

            // 更新API关联
            $resource->apis()->sync($apiIds);

            return true;
        } catch (\Exception $e) {
            \Log::error('编辑资源失败:' . json_encode($data, 320) . PHP_EOL . ' message: ' . $e->getMessage());
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
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getList($filter = [], $columns = ['*'], $pageSize = 10, $orderColumn = 'resource_id', $direction = 'desc')
    {
        $model = $this->resourceModel
            ->where('system_id', $filter['system_id'] ?? 0)
            ->when(isset($filter['status']) && $filter['status'] !== '', function ($query) use ($filter) {
                $query->where('status', $filter['status']);
            })
            ->when(isset($filter['is_dev']) && $filter['is_dev'] !== '', function ($query) use ($filter) {
                $query->where('is_dev', $filter['is_dev']);
            })
            ->when(!empty($filter['keyword']), function ($query) use ($filter) {
                $query->where('name', 'LIKE', '%' . $filter['keyword'] . '%')->whereOr('code', 'LIKE', '%' . $filter['keyword'] . '%');
            });

        $result = $model->with('apis')->select($columns)->orderBy($orderColumn, $direction)->paginate($pageSize);
        collect($result->items())->map(function ($item) {
            $item['apiIds'] = collect($item['apis'])->pluck('api_id');
            unset($item['apis']);
            return $item;
        });
        return $result;
    }

    /**
     * 获取子级
     * @param $fid
     * @param bool $children 是否获取全部子级
     * @param $systemId
     * @return array
     */
    public function getChildren($fid, $children, $systemId = PermissionConst::SYSTEM_MANAGE)
    {
        if ($children) {
            $columns = ['resource_id', 'name', 'fid'];
            $with = ['children'];
        } else {
            $columns = ['*'];
            $with = ['apis'];
            $this->resourceModel->setChildrenWith($with);
        }
        $this->resourceModel->setChildrenField($columns);

        $where = null;

        if ($children) {
            $where = [
                ['is_dev', '=', 0],
                ['status', '=', 1],
            ];
            $this->resourceModel->setChildrenWhere($where);
        }

        return $this->resourceModel
            ->where('system_id', $systemId)
            ->where('fid', $fid)
            ->when($with, function ($query) use ($with) {
                $query->with($with);
            })
            ->when(!$children, function ($query) {
                $query->with('child');
            })
            ->when($where, function ($query) use ($where) {
                $query->where($where);
            })
            ->select($columns)
            ->when(!$children, function ($query) {
                $query->withCount('child');
            })
            ->get()
            ->toArray();
    }

    /**
     * 删除
     * @param int $id
     * @return bool
     * @throws BusinessException
     */
    public function del(int $id)
    {
        $connection = DBConnection::getPermissionConnection();
        $resource = $this->resourceModel->find($id);
        if(empty($resource)){
            throw new BusinessException('‘功能’未找到');
        }
        $connection->beginTransaction();
        try {
            // 删除与角色关联
            $resource->roles()->detach();
            // 删除与API关联
            $resource->apis()->detach();

            $resource->delete();

            $connection->commit();
            $this->clearUserCache($resource['system_id'],0);
            return true;
        } catch (\Exception $e) {
            $connection->rollBack();
            \Log::error('删除资源失败【' . $id . '-' . $resource['name'] . '】失败，message: ' . $e->getMessage());
            throw new BusinessException('删除失败');
        }
    }

    public function getApiByResourceIds(array $resourceIds){
        if(empty($resourceIds)){
            return [];
        }
        return ResourceApi::getModel()->whereIn('resource_id',$resourceIds)->pluck('api_id')->toArray();
    }

    public function getRoleResourceIdsByRoleId($roleId)
    {
        return RoleResource::where('role_id', $roleId)->pluck('resource_id')->toArray();
    }
}
