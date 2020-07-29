<?php


namespace App\Http\Controllers\User;


use App\Consts\FeedbackConst;
use App\Http\Controllers\Controller;
use App\Services\User\feedbackService;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    protected $feedbackService;

    public function __construct(feedbackService $feedbackService)
    {
        $this->feedbackService = $feedbackService;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     * @throws \OSS\Core\OssException
     */
    public function add(Request $request){
        $feedback = $request->all();
        $feedback['user_id'] = $this->getUserId();
        $feedback = $this->feedbackService->feedback($feedback, $request->file()['feedback_images'] ?? []);
        return success($feedback);
    }

    public function remove(Request $request){
        $feedback = $request->input();
        $flag = $this->feedbackService->remove($this->getUserId(),$feedback['feedback_id']);
        return success(['flag'=>$flag]);
    }

    public function find(Request $request){
        $feedback = $request->input();
        $feedback = $this->feedbackService->find($this->getUserId(),$feedback['feedback_id']);
        return success($feedback);
    }

    public function findAll(Request $request){
        $list = $this->feedbackService->findAllByUid($this->getUserId(),$request->input('feedback_type',0));
        return success($list);
    }

    public function typeList(Request $request)
    {
        return success(FeedbackConst::getTypeList());
    }

}
