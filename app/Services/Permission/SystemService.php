<?php

namespace App\Services\Permission;

use App\Exceptions\BusinessException;
use App\Models\Permission\System;
use Illuminate\Validation\Rule;

class SystemService extends BasePermissionService
{
    /**
     * @var System
     */
    protected $model;

    public function __construct()
    {
        $this->model = new System();
    }

    /**
     * 添加
     * @param string $name API名
     * @param string $domain 域名
     * @param string $desc 备注
     * @return array
     * @throws BusinessException
     */
    public function add($name, $domain, $desc)
    {
        $data = [
            'system_name' => $name,
            'domain' => $domain,
            'desc' => $desc
        ];
        $validate = \Validator::make($data, [
            'system_name' => 'required|unique:sc_permission.system'
        ], [
            'system_name.required' => '名称不能为空'
        ]);
        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first());
        }

        try {
            // 添加
            $this->model->create($data);
            $id = $this->model->getKeyId();

            return ['system_id' => $id];
        } catch (\Exception $e) {
            \Log::error('添加系统失败:' . json_encode_clean($data) . PHP_EOL . ' message: ' . $e->getMessage());
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
            'system_name' => ['required', Rule::unique('sc_permission.system')->ignore($id, 'system_id')],
        ], [
            'system_name.required' => '名称不能为空'
        ]);
        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first());
        }

        $model = $this->model->getById($id);
        if (!$model) {
            throw new BusinessException('信息未找到');
        }

        try {
            // 保存
            $model->edit($id, $data);

            return true;
        } catch (\Exception $e) {
            \Log::error('编辑系统失败:' . json_encode_clean($data) . PHP_EOL . ' message: ' . $e->getMessage());
            throw new BusinessException('编辑失败');
        }
    }

    /**
     * 列表
     * @param array $columns
     * @param int $pageSize
     * @param string $orderColumn
     * @param string $direction
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getList($pageSize = 10, $columns = ['*'], $orderColumn = 'system_id', $direction = 'asc')
    {
        return $this->model->select($columns)->orderBy($orderColumn, $direction)->paginate($pageSize);
    }

    /**
     * 获取字典
     * @return array
     */
    public function getDic()
    {
        return $this->model->pluck('system_name', 'system_id')->toArray();
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
            $model = $this->model->find($id);
            if (!$model) {
                throw new BusinessException('信息未找到');
            }

            $model->delete();

            return true;
        } catch (BusinessException $e) {
            throw new BusinessException($e->getMessage());
        } catch (\Exception $e) {
            \Log::error('删除系统失败' . $id. '失败，message: ' . $e->getMessage());
            throw new BusinessException('删除失败' . $e->getMessage());
        }
    }
}
