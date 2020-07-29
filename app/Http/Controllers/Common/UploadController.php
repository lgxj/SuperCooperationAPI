<?php


namespace App\Http\Controllers\Common;


use App\Http\Controllers\Controller;
use App\Services\Common\UploadService;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    protected $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     * @throws \OSS\Core\OssException
     *
     */
    public function index(Request $request){
        $upload = $this->uploadService->upload($request->file(),$request->get('directory'));
        return success($upload);
    }

    public function signature()
    {
        return success($this->uploadService->getSignature());
    }
}
