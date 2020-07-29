<?php

namespace App\Web\Controllers\Content;

use App\Bridges\Pool\ArticleBridge;
use App\Bridges\Pool\ArticleCategoryBridge;
use App\Services\Pool\ArticleService;
use App\Services\Pool\ArticleCategoryService;
use App\Consts\GlobalConst;
use App\Web\Controllers\ScController;
use Illuminate\Http\Request;

class ArticleController extends ScController
{
    /**
     * @var ArticleService
     */
    protected $service;

    /**
     * @var ArticleCategoryService
     */
    protected $articleCategoryBridge;

    public function __construct(ArticleBridge $service, ArticleCategoryBridge $articleCategoryBridge)
    {
        $this->service = $service;
        $this->articleCategoryBridge = $articleCategoryBridge;
    }

    /**
     * 文章列表页
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \App\Exceptions\BusinessException
     */
    public function index(Request $request)
    {
        $category_id = $request->input('id');
        if (!$category_id) {
            throw new \Exception('参数错误');
        }

        $category = $this->articleCategoryBridge->getDetail($category_id);
        if (!$category) {
            throw new \Exception('分类未找到');
        }

        return view('web/content/article/list', [
            'category' => $category
        ]);
    }

    /**
     * 文章详情页
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \App\Exceptions\BusinessException
     */
    public function detail(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            throw new \Exception('参数错误');
        }

        $info = $this->service->getDetail($id);
        if (!$info) {
            throw new \Exception('文章未找到');
        }

        $category = $this->articleCategoryBridge->getDetail($info['category_id']);
        if (!$category) {
            throw new \Exception('文章分类未找到');
        }

        $data = [
            'info' => $info,
            'category' => $category
        ];

        // 需要显示关联文章
        $result = null;
        if (in_array('relation', $category['detail_fields'])) {
            $filter = [
                'category_id' => $info['category_id'],
                'unique_id' => $info['article_id']
            ];
            $columns = ['article_id', 'title', 'content_type', 'hits', 'tag', 'author', 'created_at', 'summary', 'cover', 'link'];
            $pageSize = 10;
            $result = $this->service->getList($filter, $columns, $pageSize);

            foreach ($result->items() as &$item) {
                $item['cover'] = getFullPath($item['cover']);
            }

            $data['list'] = $result->items();
        }

        return view('web/content/article/detail', $data);
    }
}
