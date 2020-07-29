<?php


namespace App\Models\Trade\Entity;


class TaskOrderEntity
{

    /**
     * @var int 用户ID
     */
    public $userId = 0;
    /**
     * @var string 订单号
     */
    public $orderNo = '';
    /**
     * @var string 订单类型
     */
    public $orderType = 0;
    /**
     * @var string 任务单名称
     */
    public $orderName = '';
    /**
     * @var string 任务单状态
     */
    public $orderState = 0;
    /**
     * @var int 任务单服务地址
     */
    public $addressId = 0;
    /**
     * @var int 任务单分类ID
     */
    public $category = 0;
    /**
     * @var int 任务单原始金额
     */
    public $originPrice = 0;
    /**
     * @var int 任务单实际支付金额
     */
    public $payPrice = 0;

    /**
     * @var int 任务单服务价格
     */
    public $changePrice = 0;
    /**
     * @var int 要求帮手星级
     */
    public $helperLevel = 0;
    /**
     * @var int 加急价格 0表示不加急
     */
    public $urgentPrice = 0;
    /**
     * @var int 保险价格 0表示不保险
     */
    public $insurancePrice = 0;
    /**
     * @var int 人脸识别价格 0表示不参加人脸识别
     */
    public $facePrice = 0;
    /**
     * @var string 视频URL
     */
    public $voiceUrl = '';
    /**
     * @var string 文本描述
     */
    public $voiceText = '';
    /**
     * @var string 备注
     */
    public $memo = '';
    /**
     * @var array 附件列表
     */
    public $attachmentList = [];

    /**
     * @var int 附件类型 1表示图片 2表示附件
     */
    public $attachmentType = 0;

    /**
     * @var string  任务开始时间
     */
    public $startTime = '';

    /**
     * @var string  任务结束时间
     */
    public $endTime = '';

    public $addressList = [

    ];
}
