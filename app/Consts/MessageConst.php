<?php


namespace App\Consts;


class MessageConst
{

    //消息大类
    const TYPE_NOTICE = 0;
    const TYPE_COMMENT = 1;
    const TYPE_CHAT = 2;
    const TYPE_ORDER = 3;

    //消息子类
    const TYPE_CHAT_GENERAL = 0;
    const TYPE_COMMENT_TASK_EMPLOYER = 1;
    const TYPE_COMMENT_TASK_HELPER = 2;
    const TYPE_ORDER_HELPER_NEW = 3;
    const TYPE_ORDER_HELPER_CONFIRM = 4;
    const TYPE_ORDER_HELPER_COMPLETE = 5;
    const TYPE_ORDER_HELPER_DEADLINE = 6;
    const TYPE_ORDER_HELPER_OVERTIME = 7;
    const TYPE_ORDER_HELPER_OVERTIME_COMPENSATE = 8;
    const TYPE_ORDER_HELPER_COMPETITION = 9;
    const TYPE_ORDER_HELPER_CANCEL = 10;
    const TYPE_ORDER_HELPER_CANCEL_COMPENSATE = 11;
    const TYPE_ORDER_EMPLOYER_RECEIVE = 12;
    const TYPE_ORDER_EMPLOYER_COMPLETE = 13;
    const TYPE_ORDER_EMPLOYER_DEADLINE = 14;
    const TYPE_ORDER_EMPLOYER_DELIVERY = 15;
    const TYPE_ORDER_EMPLOYER_COMPETITION = 16;
    const TYPE_ORDER_EMPLOYER_NO_HELPER = 17;
    const TYPE_ORDER_EMPLOYER_CANCEL = 18;
    const TYPE_ORDER_EMPLOYER_CANCEL_COMPENSATE = 19;
    const TYPE_ORDER_EMPLOYER_OVERTIME_COMPENSATE = 20;
    const TYPE_ORDER_EMPLOYER_REFUSE_DELIVERY = 21;
    const TYPE_ORDER_EMPLOYER_OVERDUE_CANCEL = 22;
    const TYPE_CHAT_IM_QQ = 100;
    const TYPE_NOTICE_WITHDRAW = 200;
    const TYPE_SEND_DEFAULT = 999;   // 默认业务码

    //个推推送类型
    const PUSH_TYPE_SINGLE = 1; // 单推
    const PUSH_TYPE_LIST = 2;   // 批量推
    const PUSH_TYPE_APP = 3;    // 群推

    public static function getOrderNoticeMessage($type,$orderName,$employerUserId,$helperUserId,$priceChange = 0){
        $list = [
            MessageConst::TYPE_ORDER_HELPER_NEW => [
                'title'=>'新任务来啦',
                'content'=>'附近有新的任务，快去抢单吧~',
                'to_user_id'=>$helperUserId
            ],
            MessageConst::TYPE_ORDER_HELPER_CONFIRM => [
                'title'=>'任务已被确认',
                'content'=>"您交付的**{$orderName}**任务已被雇主确认完成，快来谈谈你的感受吧~",
                'to_user_id'=>$helperUserId
            ],
            MessageConst::TYPE_ORDER_HELPER_COMPETITION => [
                'title'=>'任务竞价成功',
                'content'=>"您参与竞价的**{$orderName}**任务竞价成功，请记得及时完成哟~",
                'to_user_id'=>$helperUserId
            ],
            MessageConst::TYPE_ORDER_HELPER_COMPLETE => [
                'title'=>'任务确认完成',
                'content'=>"您接收的**{$orderName}**任务已被雇主确认完成，快来谈谈你的感受吧~",
                'to_user_id'=>$helperUserId
            ],
            MessageConst::TYPE_ORDER_HELPER_DEADLINE => [
                'title'=>'任务快到截止时间啦',
                'content'=>"您接受的**{$orderName}**任务已接近任务截止时间，请及时完成交付哟~",
                'to_user_id'=>$helperUserId
            ],
            MessageConst::TYPE_ORDER_HELPER_OVERTIME => [
                'title'=>'任务已逾期',
                'content'=>"您接受的**{$orderName}**任务已逾期，请记得及时交付哟~",
                'to_user_id'=>$helperUserId
            ],
            MessageConst::TYPE_ORDER_HELPER_OVERTIME_COMPENSATE => [
                'title'=>'帮手逾期交付',
                'content'=>"您发布的**{$orderName}**任务已逾期交付，预计赔付您{$priceChange}元",
                'to_user_id'=>$employerUserId
            ],
            MessageConst::TYPE_ORDER_HELPER_CANCEL => [
                'title'=>'任务被取消啦',
                'content'=>"您发布的**{$orderName}**任务已被帮手取消啦，及时查看任务 ~",
                'to_user_id'=>$employerUserId
            ],
            MessageConst::TYPE_ORDER_HELPER_CANCEL_COMPENSATE => [
                'title'=>'帮手取消赔付款到账啦',
                'content'=>"**{$orderName}**任务取消赔付款（{$priceChange}元）到账了，请注意查收哦~",
                'to_user_id'=>$employerUserId
            ],

            MessageConst::TYPE_ORDER_EMPLOYER_RECEIVE => [
                'title'=>'任务已被接单啦',
                'content'=>"您发布的**{$orderName}**任务已被帮手接单啦~",
                'to_user_id'=>$employerUserId
            ],
            MessageConst::TYPE_ORDER_EMPLOYER_DELIVERY => [
                'title'=>'任务已被提交完成',
                'content'=>"您发布的**{$orderName}**任务帮手已提交完成，请及时确认哟~",
                'to_user_id'=>$employerUserId
            ],
            MessageConst::TYPE_ORDER_EMPLOYER_COMPETITION => [
                'title'=>'任务有新的竞价啦',
                'content'=>"您发布的**{$orderName}**有新的竞价啦，请及时确认哟~",
                'to_user_id'=>$employerUserId
            ],
            MessageConst::TYPE_ORDER_EMPLOYER_DEADLINE => [
                'title'=>'发布的任务已到截止时间啦',
                'content'=>"您发布的**{$orderName}**已到订单截止时间，记得及时处理哦~",
                'to_user_id'=>$employerUserId
            ],
            MessageConst::TYPE_ORDER_EMPLOYER_NO_HELPER => [
                'title'=>'暂无帮手接单',
                'content'=>"您发布的**{$orderName}**任务暂无帮手接单，重新发布试试~",
                'to_user_id'=>$employerUserId
            ],
            MessageConst::TYPE_ORDER_EMPLOYER_COMPLETE => [
                'title'=>'任务已成功完成',
                'content'=>"本次服务还满意吗？快来说说你的想法吧~",
                'to_user_id'=>$employerUserId
            ],
            MessageConst::TYPE_ORDER_EMPLOYER_CANCEL => [
                'title'=>'任务已被雇主取消',
                'content'=>"您接受的**{$orderName}**任务已被雇主取消啦，及时查看任务详情~",
                'to_user_id'=>$helperUserId
            ],
            MessageConst::TYPE_ORDER_EMPLOYER_CANCEL_COMPENSATE => [
                'title'=>'雇主取消赔付款到账啦',
                'content'=>"**{$orderName}**任务赔付款（{$priceChange}元）到账了，请注意查收哦~",
                'to_user_id'=>$helperUserId
            ],
            MessageConst::TYPE_ORDER_EMPLOYER_OVERTIME_COMPENSATE => [
                'title'=>'任务逾期赔付款到账啦',
                'content'=>"**{$orderName}**任务逾期交付赔付款（{$priceChange}元）到账了，请注意查收哦~",
                'to_user_id'=>$employerUserId
            ],
            MessageConst::TYPE_ORDER_EMPLOYER_REFUSE_DELIVERY => [
                'title'=>'任务交付被拒绝啦',
                'content'=>"**{$orderName}**任务交付被雇主拒绝啦，请联系雇主~",
                'to_user_id'=>$helperUserId
            ],
            MessageConst::TYPE_ORDER_EMPLOYER_OVERDUE_CANCEL => [
                'title'=>'任务已到截至时间被取消了',
                'content'=>"**{$orderName}**任务已到截至时间，还未有人接单任被取消了，您支付的钱原路退款~",
                'to_user_id'=>$employerUserId
            ]
        ];
        return $list[$type] ?? [];
    }

    public static function isNotDeleteContent($subType){
        return in_array($subType,[self::TYPE_ORDER_HELPER_NEW]);
    }
    /**
     * 推送模板默认参数
     * @param $code
     * @return array
     */
    public static function getConfig($code): array
    {
        switch ($code) {
            case self::TYPE_SEND_DEFAULT:
                return [
                    'payload' => [
                        // 'action' => 'toPage',                  // 前端处理类型
                        // 'page' => '/pages/message/index',    // type=toPage时跳转页面路径
                        // 'query' => []                        // type=toPage时跳转页面参数
                    ],
                    'sms' => [
                        // 'templateId' => 'xxx',   // 短信模板ID
                        // 'params' => [],          // 短信参数
                        // 'sendTime' => 3600,      // 延迟时间(秒, 1-72小时之间，到达延迟时间未推送成功则进行短信补量)
                    ],
                    'options' => [
                        // 'sound' => '',  // 通知铃声文件名网络路径，为空时使用配置文件中默认值
                        // 'transmissionType' => 2, // 收到消息是否立即启动应用，1为立即启动，2则广播等待客户端自启动
                        // 'showStart' => '',   // 通知下发开始时间，YYMMDDHHIISS格式，为空时立即下发
                        // 'showEnd' => '',     // 通知下发结束时间
                        // 'duration' => 1440,  // 通知下发时间段时长（分钟），未设置结束时间时根据此值&showStart计算
                        // 'logoName' => '',    // 通知栏LOGO名，为空时使用配置文件中默认值
                        // 'logoUrl' => '',     // 通知栏LOGO网络路径，为空时使用配置文件中默认值
                        // 'isRing' => true,    // 收到通知是否响铃
                        // 'isVibrate' => true, // 收到通知是否振动
                        // 'channelLevel' => 3, // 通知渠道重要性，具体值有0、1、2、3、4
                    ]
                ];
                break;
            default:
                return [];
        }
    }
}
