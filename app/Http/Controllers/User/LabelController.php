<?php


namespace App\Http\Controllers\User;


use App\Consts\UserConst;
use App\Http\Controllers\Controller;
use App\Services\User\LabelService;
use Illuminate\Http\Request;

class LabelController extends Controller
{

    protected $labelService = null;

    public function __construct(LabelService $labelService)
    {
        $this->labelService = $labelService;
    }

    public function getLabels(Request $request){
        $labelType = $request->get('label_type',UserConst::LABEL_TYPE_EMPLOYER);
        $labels = $this->labelService->getRandLabels($labelType);
        return success($labels);
    }


    public function getUserHotLabels(Request $request){
        $userId = $request->get('user_id',$this->getUserId());
        $labelType = $request->get('label_type',UserConst::LABEL_TYPE_EMPLOYER);
        $labels = $this->labelService->getUserHotLabels($userId,$labelType);
        return success($labels);
    }
}
