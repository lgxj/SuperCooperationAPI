<?php
namespace App\Models\Permission;

use App\Models\ScModel;

/**
 * 权限模块基础模型
 * @package app\Models\Permission
 */
class BasePermission extends ScModel
{
    protected $connection = 'sc_permission';

    /**
     * 获取主键值
     * @return mixed
     */
    public function getKeyId()
    {
        return $this->{$this->primaryKey};
    }

    /**
     * 根据ID获取记录
     * @param int $id
     * @param array $columns
     * @return Admin
     */
    public function getById(int $id, $columns = ['*'])
    {
        return $this->find($id, $columns);
    }

    /**
     * 编辑
     * @param int $id
     * @param array $data
     * @return int
     */
    public function edit(int $id, array $data)
    {
        return $this->where($this->primaryKey, $id)->update($data);
    }
}
