<?php
namespace App\Services\Trade\Category;

use App\Consts\ErrorCode\PoolErrorCode;
use App\Exceptions\BusinessException;
use App\Models\Trade\Order\Category;
use Illuminate\Validation\Rule;

class TaskCategoryManagerService
{
    /**
     * @var Category
     */
    protected $model;

    public function __construct(Category $category)
    {
        $this->model = $category;
    }

    /**
     * 添加
     * @param string $category_name 名称
     * @param int $sort 排序
     * @return array
     * @throws BusinessException
     */
    public function add($category_name, $sort)
    {
        $data = [
            'category_name' => $category_name,
            'sort' => $sort
        ];
        $validate = \Validator::make($data, [
            'category_name' => 'required|unique:sc_trade.order_category'
        ], [
            'category_name.required' => '名称不能为空',
            'category_name.unique' => '名称已存在'
        ]);
        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first(),PoolErrorCode::TASK_CATEGORY_VALIDATION_ERROR);
        }

        try {
            $category = $this->model->create($data);
            return ['category_id' => $category->category_id];
        } catch (\Exception $e) {
            \Log::error('添加任务分类失败:' . json_encode($data, 320) . PHP_EOL . ' message: ' . $e->getMessage());
            throw new BusinessException('添加失败',PoolErrorCode::TASK_CATEGORY_SAVE_FAILED);
        }
    }

    /**
     * 编辑
     * @param $id
     * @param $category_name
     * @param $sort
     * @throws BusinessException
     */
    public function edit($id, $category_name, $sort)
    {
        $data = [
            'category_name' => $category_name,
            'sort' => $sort
        ];
        $validate = \Validator::make($data, [
            'category_name' => ['required', Rule::unique('sc_trade.order_category')->ignore($id, 'category_id')],
        ], [
            'category_name.required' => '名称不能为空',
            'category_name.unique' => '名称已存在'
        ]);
        if ($validate->fails()) {
            throw new BusinessException($validate->errors()->first(),PoolErrorCode::TASK_CATEGORY_VALIDATION_ERROR);
        }

        try {
            $this->model->where('category_id', $id)->update($data);
        } catch (\Exception $e) {
            \Log::error('编辑任务分类失败:' . json_encode($data, 320) . PHP_EOL . ' message: ' . $e->getMessage());
            throw new BusinessException('编辑失败',PoolErrorCode::TASK_CATEGORY_SAVE_FAILED);
        }
    }

    /**
     * 列表
     * @param int $pageSize
     * @param array $columns
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getList($pageSize = 10, $columns = ['*'])
    {
        $result = $this->model->select($columns)->orderByDesc('sort')->orderByDesc('category_id')->paginate($pageSize);
        return $result;
    }

    /**
     * 字典
     * @return array
     */
    public function getDic()
    {
        return $this->model->orderByDesc('sort')->orderByDesc('category_id')->get()->pluck('category_name', 'category_id')->toArray();
    }

    /**
     * 删除
     * @param $id
     * @throws BusinessException
     */
    public function del($id)
    {
        try {
            $category = $this->model->find($id);
            $category->delete();
        } catch (BusinessException $e) {
            throw new BusinessException($e->getMessage());
        } catch (\Exception $e) {
            \Log::error('删除任务分类【' . $id. '】失败，message: ' . $e->getMessage());
            throw new BusinessException('删除失败',PoolErrorCode::TASK_CATEGORY_DELETE_FAILED);
        }
    }
}
