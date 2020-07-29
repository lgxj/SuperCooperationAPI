<?php
namespace App\Services\Permission;

use App\Jobs\AdminLogJob;
use App\Models\Permission\AdminLog;

class AdminLogService extends BasePermissionService
{
    protected $adminLogModel;

    public function __construct()
    {
        $this->adminLogModel = new AdminLog();
    }

    /**
     * 创建日志
     * @param int $adminId 管理员ID
     * @param string $type 类型
     * @param string $title 标题
     * @param string $content 内容
     */
    public function create($type, $title, $content = '', $adminId = 0)
    {
        if (!$adminId) {
            $adminId = request('admin.user_id');
        }
        if (!$adminId) {
            $adminId = request('store.user_id');
        }
        if (!$adminId) {
            $adminId = request('agent.user_id');
        }
        $systemId = request('admin.system_id');
        if (!$systemId) {
            $systemId = request('store.system_id');
        }
        if (!$systemId) {
            $systemId = request('agent.system_id');
        }
        $subId = getSubId() ?: 0;
        AdminLogJob::dispatch($adminId, $type, $title, $content, $systemId, $subId);
    }

    /**
     * 保存日志
     * @param $adminId
     * @param $type
     * @param $title
     * @param $content
     * @param $systemId
     * @param $subId
     * @return bool
     */
    public function add($adminId, $type, $title, $content, $systemId, $subId)
    {
        return $this->adminLogModel->add($adminId, $type, $title, $content, $systemId, $subId);
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
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getList($filter = [], $columns = ['*'], $pageSize = 10, $systemId = 0, $subId = 0, $orderColumn = 'log_id', $direction = 'desc')
    {
        $result = $this->adminLogModel
            ->where('system_id', $systemId)
            ->where('sub_id', $subId)
            ->when(!empty($filter['admin_id']), function ($query) use ($filter) {
                $query->where('admin_id', $filter['admin_id']);
            })
            ->when(!empty($filter['time']), function ($query) use ($filter) {
                $query->whereBetween('created_at', $filter['time']);
            })
            ->when(!empty($filter['keyword']), function ($query) use ($filter) {
                $query->where('title', 'LIKE', '%' . $filter['keyword'] . '%')->whereOr('type', 'LIKE', '%' . $filter['keyword'] . '%');
            })
            ->select($columns)
            ->orderBy($orderColumn, $direction)
            ->paginate($pageSize);

        $userIds = collect($result->items())->pluck('admin_id')->toArray();
        $users = collect($this->getUserService()->users($userIds))->keyBy('user_id')->toArray();
        $phones = $this->getUserService()->getPhoneByUserIds($userIds);

        collect($result->items())->map(function (&$item) use ($users, $phones) {
            $item['admin'] = [
                'admin_id' => $item['admin_id'],
                'username' => $phones[$item['admin_id']] ?? '',
                'name' => $users[$item['admin_id']]['user_name'] ?? ''
            ];
        });

        return $result;
    }
}
