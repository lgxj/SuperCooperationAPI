<?php
namespace App\Models\Permission;

/**
 * 角色模型
 * @package app\Models\Permission
 */
class Role extends BasePermission
{
    protected $table = 'role';

    protected $primaryKey = 'role_id';

    /**
     * 关联资源
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function resources()
    {
        return $this->belongsToMany(Resource::class, 'role_resource', 'role_id', 'resource_id');
    }

    /**
     * 添加
     * @param string $name 角色名
     * @param string $remark 备注
     * @param int $systemId 备注
     * @param int $subId 备注
     * @return bool|mixed
     */
    public function add($name, $remark, $systemId = 0, $subId = 0)
    {
        $this->name = $name;
        $this->remark = $remark;
        $this->system_id = $systemId;
        $this->sub_id = $subId ?: 0;
        $this->save();
        return $this->role_id ?? false;
    }

}
