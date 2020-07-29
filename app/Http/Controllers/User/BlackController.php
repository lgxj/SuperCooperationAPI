<?php


namespace App\Http\Controllers\User;


use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Services\User\BlackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlackController extends Controller
{
    protected $blackService;

    public function __construct(BlackService $blackService)
    {
        $this->blackService = $blackService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function add(Request $request){
        $blackUserId = $request->get('black_user_id',0);
        $this->blackService->add($this->getUserId(),$blackUserId);
        return success([]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function remove(Request $request){
        $blackUserId = $request->get('black_user_id',0);
        $this->blackService->remove($this->getUserId(),$blackUserId);
        return success([]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function get(Request $request){
        $blackUserId = $request->get('black_user_id',0);
        $black = $this->blackService->get($this->getUserId(),$blackUserId);
        return success($black);
    }
}
