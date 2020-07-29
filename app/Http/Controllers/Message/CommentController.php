<?php


namespace App\Http\Controllers\Message;


use App\Consts\MessageConst;
use App\Http\Controllers\Controller;
use App\Services\Message\CommentMessageService;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    protected $commentService;

    public function __construct(CommentMessageService $commentService)
    {
        $this->commentService = $commentService;
    }

    public function getHelperComments(Request $request){
        $page = $request->get('page',1);
        $userId = $request->get('user_id',$this->getUserId());
        $list = $this->commentService->getReceiveCommentsByUserId($userId,0,MessageConst::TYPE_COMMENT_TASK_HELPER,$page);
        return success($list);
    }

    public function getEmployerComments(Request $request){
        $page = $request->get('page',1);
        $userId = $request->get('user_id',$this->getUserId());
        $list = $this->commentService->getReceiveCommentsByUserId($userId,0,MessageConst::TYPE_COMMENT_TASK_EMPLOYER,$page);
        return success($list);
    }

}
