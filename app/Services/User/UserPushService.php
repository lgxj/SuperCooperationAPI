<?php
namespace App\Services\User;

use App\Models\User\UserPush;
use App\Services\ScService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserPushService extends ScService
{

    /**
     * 根据id获取用户
     * @param int $userId
     * @return Builder|Model|object|null
     */
    public function getUserById(int $userId)
    {
        return UserPush::where('user_id', $userId)->first();
    }

    /**
     * 根据cid获取用户
     * @param string $cid
     * @return Builder|Model|object|null
     */
    public function getUserByCid(string $cid)
    {
        return UserPush::where('cid', $cid)->first();
    }

    /**
     * 添加用户推送账户信息
     * @param int $userId
     * @param string $cid
     * @param string $phone
     * @return bool
     */
    public function add(int $userId, string $cid, $phone = '')
    {
        $model = new UserPush();
        $model->user_id = $userId;
        $model->cid = $cid;
        $model->phone = $phone;
        return $model->save();
    }

    /**
     * 修改用户cid
     * @param $userId
     * @param $cid
     * @return int
     */
    public function updateCid(int $userId, $cid)
    {
        return UserPush::where('user_id', $userId)->update(['cid' => $cid]);
    }

    /**
     * 修改用户绑定手机
     * @param int $userId
     * @param $phone
     * @return int
     */
    public function updatePhone(int $userId, $phone)
    {
        return UserPush::where('user_id', $userId)->update(['phone' => $phone]);
    }

}
