<?php
namespace App\Services\Pool;

use App\Consts\ErrorCode\PoolErrorCode;
use App\Exceptions\BusinessException;
use App\Models\Pool\ArticleCategory;
use App\Services\ScService;
use Illuminate\Validation\Rule;

/**
 * 文章分类管理
 *
 * Class ArticleCategoryService
 * @package App\Services\Pool
 */
class ArticleCategoryService extends ScService
{

    /**
     * @var ArticleCategory
     */
    protected $model;



    public function __construct(ArticleCategory $articleCategory)
    {
        $this->model = $articleCategory;
    }

    /**
     * 添加
     * @param string $name 名称
     * @param int $list_type 列表样式ID
     * @param int $cover_size 封面图宽度（高度根据宽度及列表样式自动确定）
     * @param array $list_fields 列表字段
     * @param array $detail_fields 详情字段
     * @param int $photo_size 图集高度
     * @return array
     * @throws BusinessException
     */
    public function add($name, $list_type, $cover_size, $list_fields, $detail_fields, $photo_size)
    {
        $data = [
            'name' => $name,
            'list_type' => $list_type,
            'cover_size' => $cover_size,
            'list_fields' => $list_fields,
            'detail_fields' => $detail_fields,
            'photo_size' => $photo_size,
        ];
        $validate = \Validator::make($data, [
            'name' => 'required|unique:sc_pool.article_category'
        ], [
            'name.required' => '名称不能为空',
            'name.unique' => '名称已存在'
        ]);
        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first(),PoolErrorCode::ARTICLE_CATEGORY_VALIDATION_ERROR);
        }

        try {
            $category = $this->model->create($data);
            return ['article_category_id' => $category->article_category_id];
        } catch (\Exception $e) {
            \Log::error('添加文章分类失败:' . json_encode($data, 320) . PHP_EOL . ' message: ' . $e->getMessage());
            throw new BusinessException('添加失败',PoolErrorCode::ARTICLE_CATEGORY_SAVE_FAILED);
        }
    }

    /**
     * 编辑
     * @param $id
     * @param array $data 修改数据
     * @throws BusinessException
     */
    public function edit($id, $data)
    {
        $validate = \Validator::make($data, [
            'name' => ['required', Rule::unique('sc_pool.article_category')->ignore($id, 'article_category_id')],
        ], [
            'name.required' => '名称不能为空',
            'name.unique' => '名称已存在'
        ]);
        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first(),PoolErrorCode::ARTICLE_CATEGORY_VALIDATION_ERROR);
        }

        try {
            $this->model->where('article_category_id', $id)->update($data);
        } catch (\Exception $e) {
            \Log::error('编辑文章分类失败:' . json_encode($data, 320) . PHP_EOL . ' message: ' . $e->getMessage());
            throw new BusinessException('编辑失败',PoolErrorCode::ARTICLE_CATEGORY_SAVE_FAILED);
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
    public function getList($filter = [], $columns = ['*'], $pageSize = 10, $orderColumn = 'article_category_id', $direction = 'desc')
    {
        $result = $this->model->select($columns)->withCount('articles')->orderBy($orderColumn, $direction)->orderBy('article_category_id', 'desc')->paginate($pageSize);
        return $result;
    }

    /**
     * 字典
     * @return array
     */
    public function getDic()
    {
        return $this->model->get()->pluck('name', 'article_category_id')->toArray();
    }

    /**
     * 全部
     * @param array $columns
     * @return array
     */
    public function getAll($columns = ['*'])
    {
        return $this->model->select($columns)->orderBy('article_category_id', 'asc')->get()->toArray();
    }

    /**
     * 详情
     * @param $id
     * @return ArticleCategory|ArticleCategory[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     * @throws BusinessException
     */
    public function getDetail($id)
    {
        $category = $this->model->find($id);
        if (!$category) {
            throw new BusinessException('分类未找到');
        }

        return $category->toArray();
    }

    /**
     * 删除
     * @param $id
     * @throws BusinessException
     */
    public function del($id)
    {
        try {
            $category = $this->model->withCount('articles')->find($id);

            if (!$category) {
                throw new BusinessException('分类未找到',PoolErrorCode::ARTICLE_CATEGORY_NOT_EXIST);
            }

            if ($category->article_count) {
                throw new BusinessException('当前分类下有文章，不可删除',PoolErrorCode::ARTICLE_CATEGORY_HAS_CONTENT);
            }

            $category->delete();

            // 记录日志
        } catch (BusinessException $e) {
            throw new BusinessException($e->getMessage());
        } catch (\Exception $e) {
            \Log::error('删除文章分类【' . $id. '】失败，message: ' . $e->getMessage());
            throw new BusinessException('删除失败',PoolErrorCode::ARTICLE_CATEGORY_DELETE_FAILED);
        }
    }

}
