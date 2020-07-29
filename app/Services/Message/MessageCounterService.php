<?php
namespace App\Services\Message;

use App\Models\Message\MessageCounter;

/**
 * 消息中心-计数器
 *
 * Class MessageCounterService
 * @package App\Services\Message
 */
class MessageCounterService
{

    /**
     * 用户未读消息数量
     * @param $userId
     * @return array
     */
    public function getUnreadNum($userId)
    {
        $res = (new MessageCounter)->where('user_id', $userId)->select(['notice_unread_num', 'order_unread_num'])->first();
        if (!$res) {
            return [
                'order_unread_num' => 0,
                'notice_unread_num' => 0,
                'unread_num' => 0
            ];
        } else {
            $data = $res->toArray();
            $data['unread_num'] = collect($data)->sum();
            return $data;
        }
    }

}
