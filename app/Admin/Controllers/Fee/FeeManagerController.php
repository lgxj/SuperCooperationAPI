<?php


namespace App\Admin\Controllers\Fee;


use App\Admin\Controllers\ScController;
use App\Services\Trade\Fee\Admin\FeeManagerService;
use Illuminate\Http\Request;

class FeeManagerController extends ScController
{
    /**
     * @var FeeManagerService
     */
    protected $managerService;

    public function __construct(FeeManagerService $service)
    {
        $this->managerService = $service;
    }

    /**
     * 列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request){
        $filter = $request->input('filter');
        $filter = json_decode($filter, true);
        $pageSize = $request->input('limit');
        $result = $this->managerService->search($filter,$pageSize);
        return success(formatPaginate($result));
    }

    /**
     * 编辑
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function edit(Request $request)
    {
        $id = $request->post('fee_rule_id');
        $data = $request->only(['ratio', 'state']);
        $data['state'] = $data['state'] ? 1 : 0;
        $this->managerService->edit($id, $data);
        return success();
    }
}
