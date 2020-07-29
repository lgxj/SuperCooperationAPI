<?php
namespace App\Http\Controllers\Pool;

use App\Consts\GlobalConst;
use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Services\Pool\ArticleService;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    protected $articleService;

    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    /**
     * 文章列表
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function getList(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            throw new BusinessException('参数错误');
        }
        $filter = [
            'category_id' => $id
        ];
        $pageSize = $request->input('limit', GlobalConst::PAGE_SIZE);
        $columns = ['article_id', 'title', 'content_type', 'hits', 'tag', 'author', 'created_at', 'summary', 'cover', 'link'];
        $result = $this->articleService->getList($filter, $columns, $pageSize);
        $result = formatPaginate($result);
        foreach ($result['list'] as &$item) {
            $item['cover'] = getFullPath($item['cover']);
        }
        return success($result);
    }

    /**
     * 文章详情
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function getDetail(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            throw new BusinessException('参数错误');
        }
        $result = $this->service->getDetail($id);
        if (!$result) {
            throw new BusinessException('文章未找到');
        }
        return success($result);
    }

}
