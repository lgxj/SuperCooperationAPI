<?php
namespace App\Jobs\Push;

use App\Bridges\Message\PushBridge;
use App\Consts\MessageConst;
use App\Facades\Push;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * 推送任务
 * @package App\Jobs
 */
class PushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $type;
    protected $target;
    protected $title;
    protected $content;
    protected $code;
    protected $taskNo;
    protected $payload;
    protected $sms;
    protected $options;

    /**
     * PushJob constructor.
     * @param int $type 推送类型
     * @param int|array $target 推送目标
     * @param string $title 标题
     * @param string $content 内容
     * @param string $code 业务标识
     * @param int $taskNo 本地任务流水号
     * @param array $payload 消息参数
     * @param array $sms 短信补量配置
     * @param array $options 推送配置
     */
    public function __construct($type, $target, $title, $content, $code, $taskNo, $payload, $sms, $options)
    {
        $this->type = $type;
        $this->target = $target;
        $this->title = $title;
        $this->content = $content;
        $this->code = $code;
        $this->taskNo = $taskNo;
        $this->payload = $payload;
        $this->sms = $sms;
        $this->options = $options;
    }

    public function handle(PushBridge $pushBridge)
    {
        $message = Push::createMessage($this->title, $this->content, $this->payload, $this->type, $this->sms, $this->options);
        switch ($this->type) {
            case MessageConst::PUSH_TYPE_SINGLE:
                if (!$pushBridge->isBind($this->target)) {
                    \Log::info('推送失败：用户还未绑定推送客户端');
                    return;
                }
                $target = Push::createTargetByAlias($this->target);
                $result = Push::toSingle($message, $target);
                break;
            case MessageConst::PUSH_TYPE_LIST:
                $target = Push::createTargetList($this->target);
                $result = Push::toList($message, $target);
                break;
            case MessageConst::PUSH_TYPE_APP:
                $phoneTypeList = $this->target['phoneTypeList'] ?? [];
                $provinceList = $this->target['provinceList'] ?? [];
                $tagList = $this->target['tagList'] ?? [];
                $result = Push::toApp($message, $phoneTypeList, $provinceList, $tagList);
                break;
        }

        $pushBridge->saveTask($this->code, $this->taskNo, $this->type, $this->target, $this->title, $this->content, $this->payload, $this->sms, $this->options, $result);
    }
}
