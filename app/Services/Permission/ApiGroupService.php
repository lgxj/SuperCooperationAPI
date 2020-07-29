<?php

namespace App\Services\Permission;

use App\Bridges\Permission\AdminLogBridge;
use App\Exceptions\BusinessException;
use App\Models\Permission\ApiGroup;

class ApiGroupService extends BasePermissionService
{
    /**
     * @var ApiGroup
     */
    protected $apiGroupModel;

    /**
     * @var AdminLogService
     */
    protected $adminLogBridge;

    public function __construct()
    {
        $this->apiGroupModel = new ApiGroup();
        $this->adminLogBridge = new AdminLogBridge(new AdminLogService());
    }

    /**
     * 添加
     * @param int $systemId
     * @param string $name API名
     * @param int $sort 排序
     * @return array
     * @throws BusinessException
     */
    public function add($systemId, $name, $sort)
    {
        $data = [
            'system_id' => $systemId,
            'name' => $name,
            'sort' => $sort
        ];
        $validate = \Validator::make($data, [
            'system_id' => 'required',
            'name' => 'required|unique:sc_permission.api_group'
        ], [
            'system_id.required' => 'SYSTEM_ID不能为空',
            'name.required' => '分组名不能为空'
        ]);
        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first());
        }

        try {
            // 添加
            $this->apiGroupModel->add($systemId, $name, $sort);
            $id = $this->apiGroupModel->getKeyId();

            return ['api_group_id' => $id];
        } catch (\Exception $e) {
            \Log::error('添加API分组失败:' . json_encode($data, 320) . PHP_EOL . ' message: ' . $e->getMessage());
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
            'name' => 'required'
        ], [
            'name.required' => '分组名不能为空'
        ]);
        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first());
        }

        $apiGroup = $this->apiGroupModel->getById($id);
        if (!$apiGroup) {
            throw new BusinessException('分组信息未找到');
        }

        try {
            // 保存
            $apiGroup->edit($id, $data);

            return true;
        } catch (\Exception $e) {
            \Log::error('编辑API分组失败:' . json_encode($data, 320) . PHP_EOL . ' message: ' . $e->getMessage());
            throw new BusinessException('编辑失败');
        }
    }

    /**
     * 列表
     * @param int $systemId
     * @param array $columns
     * @param int $pageSize
     * @param string $orderColumn
     * @param string $direction
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getList($systemId, $columns = ['*'], $pageSize = 10, $orderColumn = 'sort', $direction = 'asc')
    {
        return $this->apiGroupModel->select($columns)->where('system_id', $systemId)->orderBy($orderColumn, $direction)->orderByDesc('api_group_id')->paginate($pageSize);
    }

    /**
     * 获取字典
     * @param int $systemId
     * @return array
     */
    public function getDic($systemId)
    {
        return $this->apiGroupModel->where('system_id', $systemId)->pluck('name', 'api_group_id')->toArray();
    }

    /**
     * 按组获取API
     * @param int $systemId
     * @return mixed
     */
    public function getTree($systemId)
    {
        return $this->apiGroupModel->where('system_id', $systemId)->with('apis:api_id,name,group_id')->get()->toArray();
    }

    /**
     * 删除
     * @param int $id
     * @return bool
     * @throws BusinessException
     */
    public function del(int $id)
    {
        try {
            $apiGroup = $this->apiGroupModel->withCount('apis')->find($id);

            if ($apiGroup->apis_count) {
                throw new BusinessException('当前分组下有接口数据，不能删除');
            }

            $apiGroup->delete();

            return true;
        } catch (BusinessException $e) {
            throw new BusinessException($e->getMessage());
        } catch (\Exception $e) {
            \Log::error('删除API分组失败' . $id. '失败，message: ' . $e->getMessage());
            throw new BusinessException('删除失败：' . $e->getMessage());
        }
    }
}
