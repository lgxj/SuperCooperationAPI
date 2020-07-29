<?php
namespace App\Admin\Controllers\Pool;

use App\Admin\Controllers\ScController;
use App\Bridges\Pool\ArticleCategoryBridge;
use App\Services\Pool\ArticleCategoryService;
use App\Consts\GlobalConst;
use Illuminate\Http\Request;

class ArticleCategoryController extends ScController
{
    /**
     * @var ArticleCategoryService
     */
    protected $service;

    public function __construct(ArticleCategoryBridge $service)
    {
        $this->service = $service;
    }

    /**
     * 列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getList(Request $request)
    {
        $filter = json_decode($request->post('filter'), true);
        $pageSize = $request->post('limit', GlobalConst::PAGE_SIZE);
        $columns = ['article_category_id', 'name', 'list_type'];
        $res = $this->service->getList($filter, $columns, $pageSize);
        $result = formatPaginate($res);
        return success($result);
    }

    /**
     * 字典
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDic()
    {
        $result = $this->service->getDic();
        return success($result);
    }

    /**
     * 字典
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAll()
    {
        $result = $this->service->getAll();
        return success($result);
    }

    /**
     * 详情
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function getDetail(Request $request)
    {
        $id = $request->input('id');
        $result = $this->service->getDetail($id);
        return success($result);
    }

    /**
     * 添加
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function add(Request $request)
    {
        $name = $request->post('name');
        $list_type = $request->post('list_type');
        $cover_size = $request->post('cover_size');
        $list_fields = $request->post('list_fields', []);
        $detail_fields = $request->post('detail_fields', []);
        $photo_size = $request->post('photo_size', []);

        $res = $this->service->add($name, $list_type, $cover_size, $list_fields, $detail_fields, $photo_size);
        return success($res);
    }

    /**
     * 编辑
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function edit(Request $request)
    {
        $id = $request->post('article_category_id');
        $data = $request->only(['name', 'list_type', 'cover_size', 'list_fields', 'detail_fields', 'photo_size']);
        $this->service->edit($id, $data);
        return success();
    }

    /**
     * 删除
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function del(Request $request)
    {
        $id = $request->post('id');
        $this->service->del($id);
        return success();
    }
}
