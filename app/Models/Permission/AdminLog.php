<?php
namespace App\Models\Permission;

/**
 * 管理员操作记录模型
 * @package app\Models\Permission
 */
class AdminLog extends BasePermission
{
    protected $table = 'admin_log';

    protected $primaryKey = 'log_id';

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
        if (is_array($content)) {
            $content = json_encode($content, 320);
        }
        $this->admin_id = $adminId;
        $this->type = $type;
        $this->title = $title;
        $this->content = $content;
        $this->system_id = $systemId;
        $this->sub_id = $subId;
        return $this->save();
    }
}
