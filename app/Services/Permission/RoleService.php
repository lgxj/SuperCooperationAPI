<?php

namespace App\Services\Permission;

use App\Consts\DBConnection;
use App\Consts\PermissionConst;
use App\Exceptions\BusinessException;
use App\Models\Permission\AdminRole;
use App\Models\Permission\Cache\ResourceCache;
use App\Models\Permission\Cache\VisitCache;
use App\Models\Permission\Role;
use App\Models\Permission\RoleResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleService extends BasePermissionService
{
    /**
     * @var Role
     */
    protected $roleModel;


    public function __construct()
    {
        $this->roleModel = new Role();
    }

    /**
     * 添加
     * @param string $name 角色名
     * @param string $remark 备注
     * @param array $resourceIds 权限
     * @param int $systemId 所属系统ID
     * @param int $subId 所属业务ID，如商家后台的商家ID
     * @return array
     * @throws BusinessException
     */
    public function add($name, $remark, array $resourceIds, int $systemId = 0,int  $subId = 0)
    {
        $data = [
            'name' => $name,
            'remark' => $remark,
            'system_id' => $systemId,
            'sub_id' => $subId
        ];
        $validate = \Validator::make($data, [
            'name' => ['required', Rule::unique('sc_permission.role')->where(function (Builder $query) use ($systemId, $subId) {
                $query->where('system_id', $systemId)->where('sub_id', $subId);
            })]

        ], [
            'name.required' => '角色名不能为空',
            'name.unique' => '角色名已存在',
        ]);
        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first());
        }

        if(empty($resourceIds)){
            throw new BusinessException('至少为角色选择一个权限');
        }

        $connection = DBConnection::getPermissionConnection();
        try {
            $connection->beginTransaction();
            // 添加
            $this->roleModel->add($name, $remark, $systemId, $subId);
            $id = $this->roleModel->role_id;
            if($resourceIds) {
                // 保存关联功能
                $this->roleModel->resources()->attach($resourceIds);
            }
            $connection->commit();
            return  $this->roleModel->toArray();
        } catch (\Exception $e) {
            $connection->rollBack();
            \Log::error('添加角色失败:' . json_encode($data, 320) . PHP_EOL . ' message: ' . $e->getMessage());
            throw new BusinessException('添加失败');
        }
    }


    /**
     * 编辑
     * @param int $id
     * @param array $data
     * @param int $systemId
     * @param int $subId
     * @return array
     * @throws BusinessException
     */
    public function edit(int $id, $systemId, $subId,array $data)
    {
        $validate = \Validator::make($data, [
            'name' => ['required', Rule::unique('sc_permission.role')->where(function (Builder $query) use ($id, $systemId, $subId) {
                return $query->where('role_id', '<>', $id)->where('system_id', $systemId)->where('sub_id', $subId);
            })]

        ], [
            'name.required' => '角色名不能为空',
            'name.unique' => '角色名已存在'
        ]);

        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first());
        }


        $role = $this->roleModel->getById($id);
        if (!$role) {
            throw new BusinessException('角色信息未找到');
        }

        if($systemId !== PermissionConst::SYSTEM_MANAGE && ($role['system_id'] != $systemId || $role['sub_id'] != $subId)){
            throw new BusinessException('您没有权限操作');
        }

        try {
            // 保存
            $role->edit($id, $data);
            return $role->toArray();
        } catch (\Exception $e) {
            \Log::error('编辑角色失败:' . json_encode($data, 320) . PHP_EOL . ' message: ' . $e->getMessage());
            throw new BusinessException('编辑失败');
        }
    }

    /**
     * 列表
     * @param array $filter
     * @param array $columns
     * @param int $pageSize
     * @param int $systemId
     * @param int $subId
     * @param string $orderColumn
     * @param string $direction
     * @return LengthAwarePaginator
     */
    public function getList($filter = [], $columns = ['*'], $pageSize = 10, $systemId = 0, $subId = 0, $orderColumn = 'role_id', $direction = 'desc')
    {
        $model = $this->roleModel
            ->where('system_id', $systemId)
            ->where(function($query) use ($subId) {
                if ($subId) {
                    $query->where('sub_id', $subId)->orWhere('sub_id', 0);
                } else {
                    $query->where('sub_id', 0);
                }
            })
            ->when(!empty($filter['keyword']), function ($query) use ($filter) {
                $query->where('name', 'LIKE',  $filter['keyword'] . '%');
            });

        $result = $model->with('resources')->select($columns)->orderBy($orderColumn, $direction)->paginate($pageSize);
        collect($result->items())->map(function ($item) {
            $item['resourceIds'] = collect($item['resources'])->pluck('resource_id');
            unset($item['resources']);
            return $item;
        });
        return $result;
    }

    /**
     * 字典
     * @param $systemId
     * @param $subId
     * @return array
     */
    public function getDic($systemId, $subId = 0)
    {
        $roles = $this->roleModel->where('system_id', $systemId)->where(function ($query) use ($subId) {
            $query->where('sub_id', $subId)->orWhere('sub_id', 0);
        })->pluck('name', 'role_id')->toArray();
        $roles[0] = '超级管理员';
        return $roles;
    }

    /**
     * 修改权限
     * @param int $id
     * @param $systemId
     * @param $subId
     * @param array $resourceIds
     * @return bool
     * @throws BusinessException
     */
    public function editResource(int $id,$systemId,$subId, array $resourceIds)
    {
        $role = $this->roleModel->getById($id);
        if(empty($role)){
            throw new BusinessException('您没有权限操作');
        }
        if($systemId !== PermissionConst::SYSTEM_MANAGE && ($role['system_id'] != $systemId || $role['sub_id'] != $subId)){
            throw new BusinessException('您没有权限操作');
        }
        if(empty($resourceIds)){
            throw new BusinessException('至少为角色选择一个权限');
        }

        try {
            $this->clearUserCache($systemId,$subId);
            // 更新资源关联
            $role->resources()->sync($resourceIds);
            return true;
        } catch (\Exception $e) {
            \Log::error('修改角色权限【' . $id. '】失败，message: ' . $e->getMessage());
            throw new BusinessException('修改角色权限失败');
        }
    }

    /**
     * 删除角色
     * @param int $id
     * @param $systemId
     * @param $subId
     * @return bool
     * @throws BusinessException
     */
    public function del(int $id,$systemId,$subId)
    {
        $permission = DBConnection::getPermissionConnection();
        $permission->beginTransaction();
        $role = $this->roleModel->find($id);
        if(empty($role)){
            throw new BusinessException('‘角色’未找到');
        }
        if($systemId !== PermissionConst::SYSTEM_MANAGE && ($role['system_id'] != $systemId || $role['sub_id'] != $subId)) {
            throw new BusinessException('您没有权限操作');
        }
        try {
            $this->clearUserCache($systemId,$subId);
            // 删除与管理员关联
            (new AdminRole)->where('role_id', $id)->delete();
            // 删除与资源关联
            $role->resources()->detach();
            $role->delete();
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('删除角色失败【' . $id . '-' . $role['name']. '】失败，message: ' . $e->getMessage());
            throw new BusinessException('删除失败');
        }
    }

    public function getRolesBySubId($systemId,$subId,$page = 1,$pageSize = 20){
        $roles = Role::getModel()->where(['system_id'=>$systemId])
            ->where(function ($query) use ($subId) {
                $query->where('sub_id', 0)->orWhere('sub_id', $subId);
            })
            ->select('role_id','name','sub_id','remark')
            ->orderBy('role_id')
            ->forPage($page,$pageSize)
            ->get()->toArray();
        if(empty($roles)){
            return [];
        }
        return $roles;
    }


    public function getRoleTemplate($systemId){
        $roles = $this->roleModel->where(['system_id'=>$systemId,'sub_id'=>PermissionConst::CREATOR_INIT_SUB_ID])->get()->toArray();
        if(empty($roles)){
            return [];
        }
        $roleIds = data_get($roles,'*.role_id',[]);
        $roleResources = RoleResource::whereIn('role_id',$roleIds)->groupBy('role_id')->get()->toArray();
        foreach ($roles as $key=>$role){
            $roleId = $role['role_id'];
            $roles['resources'] = $roleResources[$roleId] ?? [];
         }
        return $roles;
    }

    public function getUserSubs($userId,$systemId){
        if($userId <= 0){
            return [];
        }
        $subs = AdminRole::getModel()->where(['system_id'=>$systemId,'user_id'=>$userId])->distinct('sub_id')->pluck('sub_id')->toArray();
        return $subs;
    }

    /**
     * @param $userId
     * @param $systemId
     * @param $subId
     * @return array|mixed|string
     */
    public function getUserSubRoles($userId,$systemId,$subId){
        if($userId <= 0){
            return [];
        }
        $roleIds = AdminRole::getModel()->where(['system_id'=>$systemId,'user_id'=>$userId,'sub_id'=>$subId])->distinct('role_id')->pluck('role_id')->toArray();
        return $roleIds;
    }

    /**
     * 判断否是对应系统用户
     * @param $userId
     * @param $systemId
     * @return bool
     */
    public function isSystemUser($userId, $systemId)
    {
        return !!AdminRole::getModel()->where(['system_id'=>$systemId,'user_id'=>$userId])->first();
    }

    public function getByIds($roleIds)
    {
        return Role::getModel()->whereIn('role_id', $roleIds)->get();
    }

    public function getResourcesByRoleIds(array $roleIds){
        if(empty($roleIds)){
            return [];
        }
        $resourceIds = RoleResource::getModel()->whereIn('role_id',$roleIds)->pluck('resource_id')->toArray();
        return $resourceIds;
    }

    public function saveUserRole($userId,$roleId,$subId,$systemId){
        if($userId <=0 || $subId <= 0){
            throw new BusinessException('参数错误');

        }
        $adminRole = new AdminRole();
        $exist = $adminRole->where(['user_id'=>$userId,'sub_id'=>$subId,'role_id'=>$roleId])->first();
        if($exist){
            throw new BusinessException('用户所属角色已存在');
        }
        $adminRole->system_id = $systemId;
        $adminRole->role_id = $roleId;
        $adminRole->user_id = $userId;
        $adminRole->sub_id = $subId;
        $adminRole->save();
        return $adminRole->toArray();
    }

    public function checkPermissionWithCache($userId,$systemId,$subId,$apiId){
        if($userId <= 0 || $apiId <= 0){
            return false;
        }
        $cachePerm = $this->checkPermissionFromCache($systemId,$subId,$apiId,$userId);
        if(!is_null($cachePerm) && $cachePerm > 0){
            return $cachePerm == 1 ? true : false;
        }
        $roleIds = $this->getUserSubRoles($userId,$systemId,$subId);
        if(empty($roleIds)){
            return false;
        }
        //是创始人
        if(in_array(PermissionConst::CREATOR_ROLE_ID,$roleIds)){
            return true;
        }
        $roleResources = $this->getResourcesByRoleIds($roleIds);
        $resourcesIds = data_get($roleResources,'*.resource_id');
        if(empty($resourcesIds)){
            return false;
        }
        $apiIds = (new ResourceService())->getApiByResourceIds($resourcesIds);
        $perm =  in_array($apiId,$apiIds);
        if(!is_null($cachePerm)) {
            $this->setPermissionToCache($systemId, $subId, $apiId, $userId, $perm);
        }
        return $perm;
    }

    public function getResourceIds($userId,$systemId,$subId){
        $resourceIds = $this->getResourceIdsFromCache($systemId,$subId,$userId);
        if(!is_null($resourceIds) && $resourceIds){
            return $resourceIds;
        }
        $roleIds = $this->getUserSubRoles($userId,$systemId,$subId);
        if(empty($roleIds)){
            return [];
        }
        $roleResources = $this->getResourcesByRoleIds($roleIds);
        $resourcesIds = data_get($roleResources,'*.resource_id');
        if(empty($resourcesIds)){
            return [];
        }
        if(!is_null($resourceIds)) {
            $this->setResourceIdsToCache($systemId, $subId, $userId, $resourceIds);
        }
        return $resourcesIds;
    }

    /**
     * 保存各SUB-ID下超级用户
     * @param $systemId
     * @param $subId
     * @param $userId
     * @return bool
     */
    public function saveSuperUser($systemId, $subId, $userId)
    {
        return (new AdminRole)->insert([
            'system_id' => $systemId,
            'sub_id' => $subId,
            'user_id' => $userId
        ]);
    }

    /**
     * @param int $systemId
     * @param int $subId
     * @param int $userId
     * @param int $apiId
     * @return int  0表未命中缓存 1表示有权限 2表示没权限,3表示缓存异常
     */
    protected  function checkPermissionFromCache($systemId,$subId,$apiId,$userId){
        try{
            $visitCache = new VisitCache([$systemId,$subId]);
            return $visitCache->permission($userId,$apiId);
        }catch (\Exception $e){
            return 0;
        }
    }

    /**
     * @param int $systemId
     * @param int $subId
     * @param int $apiId
     * @param int $userId
     * @param bool $perm
     * @return int
     */
    protected  function setPermissionToCache($systemId,$subId,$apiId,$userId,bool $perm){
        try{
            $visitCache = new VisitCache([$systemId,$subId]);
            return $visitCache->cache($userId,$apiId,$perm);
        }catch (\Exception $e){
            return null;
        }
    }

    protected  function getResourceIdsFromCache($systemId,$subId,$userId){
        try{
            $resourceCache = new ResourceCache([$systemId,$subId]);
            return $resourceCache->resourceIds($userId);
        }catch (\Exception $e){
            return null;
        }
    }

    protected  function setResourceIdsToCache($systemId,$subId,$userId,array $resourceIds){
        try{
            $resourceCache = new ResourceCache([$systemId,$subId]);
            return $resourceCache->cache($userId,$resourceIds);
        }catch (\Exception $e){
            return null;
        }
    }
}
