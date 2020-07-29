<?php


namespace App\Http\Controllers\User;


use App\Http\Controllers\Controller;
use App\Services\User\BankCardService;
use Illuminate\Http\Request;

class BankController extends Controller
{

    protected $bankService;

    public function __construct(BankCardService $bankCardService)
    {
        $this->bankService = $bankCardService;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     * @throws \OSS\Core\OssException
     */
    public function add(Request $request){
        $bank = $request->all();
        $bank['user_id'] = $this->getUserId();
        $bank = $this->bankService->addUserBank($bank);
        return success($bank);
    }

    public function remove(Request $request){
        $bank = $request->input();
        $flag = $this->bankService->remove($this->getUserId(),$bank['bank_id']);
        return success(['flag'=>$flag]);
    }

    public function find(Request $request){
        $bank = $request->input();
        $bank = $this->bankService->find($this->getUserId(),$bank['bank_id']);
        return success($bank);
    }

    public function findAll(Request $request){
        $list = $this->bankService->findAllByUid($this->getUserId());
        return success($list);
    }

}
