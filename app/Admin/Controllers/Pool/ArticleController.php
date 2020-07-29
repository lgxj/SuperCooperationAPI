<?php
namespace App\Admin\Controllers\Pool;

use App\Admin\Controllers\ScController;
use App\Bridges\Pool\ArticleBridge;
use App\Consts\GlobalConst;
use App\Services\Pool\ArticleService;
use Illuminate\Http\Request;

class ArticleController extends ScController
{
    /**
     * @var ArticleService
     */
    protected $service;

    public function __construct(ArticleBridge $service)
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
        $columns = ['article_id', 'category_id', 'title', 'content_type', 'hits', 'tag', 'author', 'sort', 'created_at'];
        $res = $this->service->getList($filter, $columns, $pageSize);
        $result = formatPaginate($res);
        return success($result);
    }

    /**
     * 获取详情
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
        $category_id = $request->post('category_id');
        $title = $request->post('title');
        $content_type = $request->post('content_type');
        $link = $request->post('link');
        $tag = $request->post('tag');
        $author = $request->post('author');
        $summary = $request->post('summary');
        $cover = $request->post('cover');
        $photos = $request->post('photos');
        $content = $request->post('content');
        $sort = $request->post('sort');

        $res = $this->service->add($category_id, $title, $content_type, $link, $tag, $author, $cover, $summary, $content, $photos, $sort);
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
        $id = $request->input('article_id');
        $data = $request->only(['category_id', 'title', 'content_type', 'link', 'tag', 'author', 'summary', 'cover', 'photos', 'content', 'sort']);
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
        $id = $request->input('id');
        $this->service->del($id);
        return success();
    }
}
