<?php
namespace App\Admin\Controllers\Common;

use App\Admin\Controllers\ScController;
use App\Consts\UploadFileConst;
use App\Services\Common\UploadService;
use Illuminate\Http\Request;

class UploadController extends ScController
{
    protected $uploadService;

    public function __construct(UploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /**
     * 文件上传
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     * @throws \OSS\Core\OssException
     */
    public function image(Request $request)
    {
        $directory = $request->get('directory');
        $directory = $directory ?: ('uploads' . date('/Ymd'));
        $businessType = $request->get('businessType', UploadFileConst::BUSINESS_TYPE_GENERAL);
        $upload = $this->uploadService->upload($request->file(), $directory, $businessType);
        return success($upload);
    }
}
