<?php


namespace App\Admin\Controllers\Task;


use App\Admin\Controllers\ScController;
use App\Bridges\Trade\Admin\HelperManagerBridge;
use App\Services\Trade\Order\Admin\HelperManagerService;
use Illuminate\Http\Request;

class HelperManagerController extends ScController
{

    /**
     * @var HelperManagerService
     */
    protected $managerService;

    public function __construct(HelperManagerBridge $service)
    {
        $this->managerService = $service;
    }

    public function search(Request $request){
        $filter = $request->input('filter');
        $filter = json_decode($filter, true);
        $pageSize = $request->input('limit');
        $result = $this->managerService->search($filter,$pageSize);
        return success(formatPaginate($result));
    }
}
