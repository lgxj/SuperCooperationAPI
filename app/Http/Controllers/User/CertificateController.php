<?php


namespace App\Http\Controllers\User;


use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Services\User\SkillCertifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OSS\Core\OssException;

class CertificateController extends Controller
{

    protected $skillService;

    public function __construct(SkillCertifyService $skillService)
    {
        $this->skillService = $skillService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     * @throws OssException
     */
    public function add(Request $request){
        $certificate = $request->all();
        $certificate['user_id'] = $this->getUserId();
        $files = [];
        if(isset($request->file()['original_url'])){
            $files['original_url'] = $request->file()['original_url'];
        }
        if(isset($request->file()['copy_url'])){
            $files['copy_url'] = $request->file()['copy_url'];
        }
        $certificate = $this->skillService->addCertificate($certificate,$request->file());
        return success($certificate);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     * @throws OssException
     */
    public function update(Request $request){
        $certificate = $request->input();
        $certificate['user_id'] = $this->getUserId();
        $certificate = $this->skillService->updateCertificate($certificate,$request->file());
        return success($certificate);
    }

    public function remove(Request $request){
        $certificate = $request->input();
        $flag = $this->skillService->remove($this->getUserId(),$certificate['certify_id']);
        return success(['flag'=>$flag]);
    }

    public function find(Request $request){
        $certificate = $request->input();
        $certificate = $this->skillService->find($this->getUserId(),$certificate['certify_id']);
        return success($certificate);
    }

    public function findAll(Request $request){
        $list = $this->skillService->findAllByUid($this->getUserId());
        return success($list);
    }

    public function typeList(){
        $typeList = $this->skillService->getLicenseList();
        return success($typeList);
    }

}
