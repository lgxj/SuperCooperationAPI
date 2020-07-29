<?php
namespace App\Bridges\Trade\Category;

use App\Bridges\ScBridge;
use App\Services\Trade\Category\TaskCategoryManagerService;

class TaskCategoryManagerBridge extends ScBridge
{
    public function __construct(TaskCategoryManagerService $service)
    {
        $this->service = $service;
    }
}
