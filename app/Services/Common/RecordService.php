<?php
namespace App\Services\Common;

use App\Exceptions\BusinessException;
use App\Services\ScService;

/**
 * 语音发单转码服务
 *
 * Class RecordService
 * @package App\Services\Common
 */
class RecordService extends ScService
{
    /**
     * 录音文件转码、分片&储存
     * @param $userId
     * @param $file
     * @param bool $save 是否需要保存
     * @return array
     * @throws BusinessException
     * @throws \OSS\Core\OssException
     */
    public function transform($userId, $file, $save = false)
    {
        if (!$file) {
            throw new BusinessException('录音文件未上传');
        }
        $dir = storage_path() . '/app/uploads/temp/';
        $mp3File = $userId . '-' . time() . '-' . rand(1000, 9999). '.mp3';
        $pcmFile = $dir . $userId . '-' . time() . '-' . rand(1000, 9999). '.pcm';
        $output = '';
        $status = 0;
        $data = [];
        // 声音文件需要保存
        if ($save) {
            $result = (new UploadService())->upload([$file], 'record');
            $data['path'] = current($result);
        }

        $file->move($dir, $mp3File);
        // mp3转pcm
        $ffmpegPath = env('FFMPEG_PATH', '') . 'ffmpeg';
        exec($ffmpegPath . ' -y -i ' . $dir . $mp3File . ' -acodec pcm_s16le -f s16le -ac 1 -ar 16000 ' . $pcmFile. ' 2>&1',$output,$status);
        if($status == 1){
            \Log::error("convert pcm output", [$output]);
            @unlink($dir . $mp3File);
            throw new BusinessException('录音文件转码错误');
        }
        $f = fopen($pcmFile, 'rb');

        // 分片
        $result = [];
        $len = 1028;
        $size = filesize($pcmFile);
        for ($i = 0; $i < $size; $i += $len) {
            $tmp = stream_get_contents($f, $len, $i);
            $tmp = base64_encode($tmp);
            $result[] = $tmp;
        }
        fclose($f);

        // 删除本地文件
        @unlink($dir . $mp3File);
        @unlink($pcmFile);

        $data['list'] = $result;

        return $data;
    }
}
