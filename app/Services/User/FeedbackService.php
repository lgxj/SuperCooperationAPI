<?php


namespace App\Services\User;


use App\Consts\ErrorCode\UserErrorCode;
use App\Consts\FeedbackConst;
use App\Consts\UploadFileConst;
use App\Exceptions\BusinessException;
use App\Models\User\UserFeedback;
use App\Services\ScService;
use App\Utils\AliyunOss;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * 反馈服务层
 *
 * Class feedbackService
 * @package App\Services\User
 */
class feedbackService extends ScService
{

    /**
     * @param array $feedback
     * @param array $files
     * @return array
     * @throws BusinessException
     * @throws \OSS\Core\OssException
     */
    public function feedback(array $feedback,array $files)
    {
        $validate = Validator::make($feedback,[
            'user_id'=>'required|integer',
            'feedback_type'=>[Rule::in(array_keys(FeedbackConst::getTypeList())),'required','integer'],
            'feedback_desc'=>'required'
        ],[
            'user_id.required' => '用户ID不能为空',
            'feedback_type.required' => '反馈问题/建议类型不能为空',
            'feedback_desc.required'=>"反馈问题/建议描述不能为空"
        ]);
        if($validate->fails()){
            throw new BusinessException($validate->errors()->first(),UserErrorCode::FEEDBACK_VALIDATION_ERROR);
        }

        $fileContents = [];
        foreach ($files as $key=>$file){
            $fileContents[] = getFileContent($file,'feedback',UploadFileConst::BUSINESS_TYPE_GENERAL);
        }

        $oss = new AliyunOss();
        $ossUrlList = [];
        foreach ($fileContents as $fileContent){
            list($fileName,$fileContent) = $fileContent;
            $response = $oss->uploadFile($fileName,$fileContent,true);
            $ossUrlList[] = $response['info']['url'] ?? '';
        }
        $userFeedbackModel = new UserFeedback();
        $fields = $userFeedbackModel->getTableColumns();
        foreach ($fields as $field) {
            if ($field == $userFeedbackModel->getKeyName()) {
                continue;
            }
            if (isset($feedback[$field])) {
                $userFeedbackModel->$field = $feedback[$field];
            }
        }
        $userFeedbackModel->feedback_images = json_encode($ossUrlList);
        $userFeedbackModel->save();
        return $userFeedbackModel->toArray();
    }


    public function find(int $userId,int $id) : array
    {
        if($userId <= 0 || $id <= 0){
            return [];
        }
        $data = UserFeedback::where(['user_id'=>$userId,'feedback_id'=>$id])->first();
        return $data ? $data->toArray() : [];
    }

    public function remove(int $userId ,int $id) : bool
    {
        if($userId <= 0 || $id <= 0){
            return false;
        }
        $flag = UserFeedback::where(['user_id'=>$userId,'feedback_id'=>$id])->delete();
        return $flag > 0 ? true : false;
    }

    /**
     * 删除反馈
     * @param int $id
     * @return bool
     * @throws BusinessException
     */
    public function del(int $id)
    {
        if (UserFeedback::where('feedback_id', $id)->delete()) {
            // 添加操作日志
            return true;
        }
        throw new BusinessException('删除失败');
    }

    public function findAllByUid(int $userId,int $feedback=0,$pageSize=10) : array
    {
        if($userId <= 0){
            return [];
        }
        $list = UserFeedback::where(['user_id'=>$userId])->when($feedback>0,function ($query) use($feedback){
            $query->where('feedback_type', $feedback);
        })->orderByDesc('feedback_id')->paginate($pageSize);
        if(empty($list)){
            return [];
        }
        $data = $this->buildData($list->items());
        $total = $list->total();
        return ['items'=>$data,'total'=>$total,'page_size'=>$pageSize];
    }

    public function findAll(Carbon $startTime = null,Carbon $endTime = null,int $feedback=0,$pageSize=20) : array
    {
        $list = UserFeedback::when($feedback>0,function ($query) use($feedback){
            $query->where('feedback_type', $feedback);
        })->when($startTime,function ($query) use ($startTime){
            $query->where('created_at','>=', $startTime);
        })->when($endTime,function ($query) use ($endTime){
            $query->where('created_at','<=', $endTime);
        })
          ->orderByDesc('feedback_id')->paginate($pageSize);
        if(empty($list)){
            return [];
        }
        $data = $this->buildData($list->items());
        $total = $list->total();
        return ['items'=>$data,'total'=>$total,'page_size'=>$pageSize];
    }

    /**
     * 分页数据
     * @param $filter
     * @param $columns
     * @param int $pageSize
     * @return array|\Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getListByPage($filter, $pageSize = 10, $columns = ['*'])
    {
        $list = UserFeedback::when(!empty($filter['user_id']), function ($query) use($filter) {
                $query->where('user_id', $filter['user_id']);
            })->when(!empty($filter['feedback_type']), function ($query) use($filter) {
                $query->where('feedback_type', $filter['feedback_type']);
            })
            ->when(!empty($filter['time']), function ($query) use ($filter) {
                $query->whereBetween('created_at', $filter['time']);
            })
            ->when(!empty($filter['keyword']), function ($query) use ($filter) {
                $query->where('feedback_desc', 'LIKE', '%' . $filter['keyword'] . '%');
            })
            ->with('user:user_id,user_name,user_avatar')
            ->select($columns)
            ->orderByDesc('feedback_id')->paginate($pageSize);
            if(empty($list)){
                return [];
            }
        return $list;
    }

    protected function buildData(array $data){
        $userIds = array_unique(array_column($data,'user_id'));
        $userService = new UserService();
        $users = $userService->users($userIds);
        collect($data)->map(function ($item) use ($users)
        {
            $user_id = $item['user_id'];
            $item['user_name'] = '';
            $item['user_avatar'] = '';
            if(isset($users[$user_id]))
            {
                $user = $users[$user_id];
                $item['user_name'] = $user['user_name'];
                $item['user_avatar'] = $user['user_avatar'];
            }
            $item['feedback_type_desc'] = FeedbackConst::getTypeDesc($item['feedback_type']);
            return $item;

        });
        return $data;
    }
}
