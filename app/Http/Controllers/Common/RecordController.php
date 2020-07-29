<?php
namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Services\Common\RecordService;
use Illuminate\Http\Request;

class RecordController extends Controller
{
    protected $service;

    public function __construct(RecordService $service)
    {
        $this->service = $service;
    }

    /**
     * 录音文件转码、分片&储存
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     * @throws \OSS\Core\OssException
     */
    public function transform(Request $request)
    {
        $file = $request->file('audio');
        $save = $request->get('save', false);
        $value = $this->service->transform($this->getUserId(), $file, $save);
        return success($value);
    }

}
