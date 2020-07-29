<?php
namespace App\Jobs;

use App\Services\Permission\AdminLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class AdminLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $adminId;
    protected $type;
    protected $title;
    protected $content;
    protected $systemId;
    protected $subId;

    public function __construct($adminId, $type, $title, $content, $systemId, $subId)
    {
        $this->adminId = $adminId;
        $this->type = $type;
        $this->title = $title;
        $this->content = $content;
        $this->systemId = $systemId;
        $this->subId = $subId;
    }

    public function handle(AdminLogService $adminLogService)
    {
        $adminLogService->add($this->adminId, $this->type, $this->title, $this->content, $this->systemId, $this->subId);
    }
}
