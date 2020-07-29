<?php
namespace App\Services\Pool;

use App\Consts\ErrorCode\PoolErrorCode;
use App\Exceptions\BusinessException;
use App\Models\Pool\Article;
use App\Services\ScService;

/**
 * 文章管理
 *
 * Class ArticleService
 * @package App\Services\Pool
 */
class ArticleService extends ScService
{

    /**
     * @var Article
     */
    protected $model;



    public function __construct(Article $article)
    {
        $this->model = $article;
    }

    /**
     * 添加
     * @param int $category_id 所属类型ID
     * @param string $title 标题
     * @param int $content_type 内容类型。1：内部内容；2：外链地址
     * @param string $link 外链
     * @param array $tag 标签
     * @param string $author 作者
     * @param string $cover 封面图
     * @param string $summary 摘要
     * @param string $content 内容
     * @param array $photos 图片集
     * @param int $sort 排序
     * @return array
     * @throws BusinessException
     */
    public function add($category_id, $title, $content_type, $link, $tag, $author, $cover, $summary, $content, $photos, $sort)
    {
        $data = [
            'category_id' => $category_id,
            'title' => $title,
            'cover' => $cover,
            'summary' => $summary,
            'content' => $content,
            'content_type' => $content_type,
            'link' => $link,
            'tag' => $tag,
            'author' => $author,
            'photos' => $photos,
            'sort' => $sort
        ];
        $validate = \Validator::make($data, [
            'category_id' => 'required',
            'title' => 'required',
            'content' => 'required'
        ], [
            'category_id.required' => '分类不能为空',
            'title.required' => '标题不能为空',
            'content.required' => '内容不能为空'
        ]);
        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first(),PoolErrorCode::ARTICLE_VALIDATION_ERROR);
        }

        try {
            $article = $this->model->create($data);
            return ['article_id' => $article->article_id];
        } catch (\Exception $e) {
            \Log::error('添加文章失败:' . json_encode($data, 320) . PHP_EOL . ' message: ' . $e->getMessage());
            throw new BusinessException('添加失败',PoolErrorCode::ARTICLE_SAVE_FAILED);
        }
    }

    /**
     * 编辑
     * @param $id
     * @param array $data
     * @throws BusinessException
     */
    public function edit($id, $data)
    {
        $validate = \Validator::make($data, [
            'category_id' => 'required',
            'title' => 'required',
            'content' => 'required'
        ], [
            'category_id.required' => '分类不能为空',
            'title.required' => '标题不能为空',
            'content.required' => '内容不能为空'
        ]);
        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first(),PoolErrorCode::ARTICLE_VALIDATION_ERROR);
        }

        try {
            $this->model->where('article_id', $id)->update($data);
        } catch (\Exception $e) {
            \Log::error('编辑文章失败:' . json_encode($data, 320) . PHP_EOL . ' message: ' . $e->getMessage());
            throw new BusinessException('编辑失败',PoolErrorCode::ARTICLE_SAVE_FAILED);
        }
    }

    /**
     * 列表
     * @param array $filter
     * @param array $columns
     * @param int $pageSize
     * @param string $orderColumn
     * @param string $direction
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getList($filter = [], $columns = ['*'], $pageSize = 10, $orderColumn = 'sort', $direction = 'desc')
    {
        $result = $this->model
            ->when(!empty($filter['category_id']), function ($query) use ($filter) {
                $query->where('category_id', $filter['category_id']);
            })
            ->when(!empty($filter['unique_id']), function ($query) use ($filter) {
                $query->where('article_id', '<>', $filter['unique_id']);
            })
            ->when(!empty($filter['keyword']), function ($query) use ($filter) {
                $query->where('title', 'LIKE', '%' . $filter['keyword'] . '%')
                    ->whereOr('author', 'LIKE', '%' . $filter['keyword'] . '%')
                    ->whereOr('tag', 'LIKE', '%' . $filter['keyword'] . '%')
                    ->whereOr('summary', 'LIKE', '%' . $filter['keyword'] . '%');
            })
            ->select($columns)->orderBy($orderColumn, $direction)->orderBy('article_id', 'desc')->paginate($pageSize);
        return $result;
    }

    /**
     * 文章详情
     * @param $id
     * @param $getCategory
     * @param bool $addHits
     * @return Article|Article[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     * @throws BusinessException
     */
    public function getDetail($id, $getCategory = false, $addHits = false)
    {
        $article = $this->model->when($getCategory, function ($query) {
            $query->with('category');
        })->find($id);

        if (!$article) {
            throw new BusinessException('文章未找到');
        }

        if ($addHits) {
            $article->increment('hits');
        }

        return $article->toArray();
    }

    /**
     * 删除
     * @param $id
     * @throws BusinessException
     */
    public function del($id)
    {
        try {
            $article = $this->model->find($id);

            if (!$article) {
                throw new BusinessException('文章未找到',PoolErrorCode::ARTICLE_NOT_EXIST);
            }

            $article->delete();

        } catch (BusinessException $e) {
            throw new BusinessException($e->getMessage());
        } catch (\Exception $e) {
            \Log::error('删除文章【' . $id. '】失败，message: ' . $e->getMessage());
            throw new BusinessException('删除失败',PoolErrorCode::ARTICLE_DELETE_FAILED);
        }
    }

}
