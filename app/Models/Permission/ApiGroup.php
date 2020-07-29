<?php
namespace App\Models\Permission;

/**
 * 后台接口分组模型
 * @package app\Models\Permission
 */
class ApiGroup extends BasePermission
{
    protected $table = 'api_group';

    protected $primaryKey = 'api_group_id';

    /**
     * 关联API
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function apis()
    {
        return $this->hasMany(Api::class, 'group_id', 'api_group_id');
    }

    /**
     * 写入数据
     * @param int $systemId
     * @param $name
     * @param $sort
     * @return bool|mixed
     */
    public function add($systemId, $name, $sort)
    {
        $this->system_id = $systemId;
        $this->name = $name;
        $this->sort = $sort;
        $this->save();
        return $this->api_group_id ?? false;
    }
}
