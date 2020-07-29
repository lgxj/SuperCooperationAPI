<?php


namespace App\Services\Message\Entity;


use App\Consts\MessageConst;

class CommentEntity
{

    /**
     * @var int 发送者
     */
    public $userId = 0;
    /**
     * @var int 接收者
     */
    public $toUserId = 0;
    /**
     * @var string 消息标题
     */
    public $title = '';
    /**
     * @var string 消息内容
     */
    public $content = '';
    /**
     * @var string 消息分值
     */
    public $score = 0;
    /**
     * @var string 附件Url列表
     */
    public $attachmentList = [];
    /**
     * @var string 业务ID
     */
    public $businessId = 0;
    /**
     * @var string 扩展字段
     */
    public $extra = [];
    /**
     * @var int 子业务评论类型
     */
    public $subType = MessageConst::TYPE_COMMENT_TASK_EMPLOYER;
    /**
     * @var int 被接收者状态
     */
    public $state = 1;
}
