<?php
namespace App\Models\Permission;

/**
 * 后台接口模型
 * @package app\Models\Permission
 */
class Api extends BasePermission
{
    protected $table = 'api';

    protected $primaryKey = 'api_id';

    /**
     * 关联资源
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function resources()
    {
        return $this->belongsToMany(Resource::class, 'api_id', 'resource_id');
    }

    /**
     * 根据ID获取管理员
     * @param int $id
     * @param array $columns
     * @return Admin|Admin[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function getById(int $id, $columns = ['*'])
    {
        return $this->find($id, $columns);
    }

    /**
     * 添加
     * @param int $group_id 所属API组
     * @param string $name 名称
     * @param string $path 地址
     * @param string $method 请求方式
     * @param int $sort 排序
     * @param int $status 状态
     * @param int $is_dev 是否开发中
     * @param int $need_power 是否需要相关权限
     * @param string $remark 备注
     * @param $systemId
     * @return bool|mixed
     */
    public function add($group_id, $name, $path, $method, $sort, $status, $is_dev, $need_power, $remark,$systemId)
    {
        $this->group_id = $group_id;
        $this->name = $name;
        $this->path = $path;
        $this->method = $method;
        $this->sort = $sort;
        $this->status = $status;
        $this->is_dev = $is_dev;
        $this->need_power = $need_power;
        $this->remark = $remark;
        $this->system_id= $systemId;
        $this->save();
        return $this->api_id ?? false;
    }
}
