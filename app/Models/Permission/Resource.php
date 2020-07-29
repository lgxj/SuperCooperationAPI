<?php
namespace App\Models\Permission;

use App\Models\EloquentTree;

/**
 * 后台资源模型
 * @package app\Models\Permission
 */
class Resource extends BasePermission
{
    use EloquentTree;

    protected $table = 'resource';

    protected $primaryKey = 'resource_id';

    static $child_fields = ['id', 'pid', 'resource_name as title'];
    static $child_where = null;
    static $pid_name = 'fid';
    static $child_with = null;
    static $child_has = null;
    static $staticPrimaryKey = 'resource_id';

    /**
     * 关联角色
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_resource', 'resource_id', 'role_id');
    }

    /**
     * 关联API
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function apis()
    {
        return $this->belongsToMany(Api::class, 'resource_api', 'resource_id', 'api_id');
    }

    /**
     * 添加
     * @param int $fid 父级ID
     * @param string $name 名称
     * @param int $type 类型
     * @param string $code 编码
     * @param int $sort 排序
     * @param int $status 状态
     * @param int $is_dev 是否开发中
     * @param string $remark 备注
     * @param int $systemId
     * @return bool|mixed
     */
    public function add($fid, $name, $type, $code, $sort, $status, $is_dev, $remark, $systemId)
    {
        $this->fid = $fid;
        $this->name = $name;
        $this->type = $type;
        $this->code = $code;
        $this->sort = $sort;
        $this->status = $status;
        $this->is_dev = $is_dev;
        $this->remark = $remark;
        $this->system_id = $systemId;
        $this->save();
        return $this->resource_id ?? false;
    }
}
