<?php
namespace App\Services\Message;

use App\Models\Message\ImUser;
use App\Services\ScService;

/**
 * 腾讯IM即时聊天用户绑定
 *
 * Class IMUserService
 * @package App\Services\Message
 */
class IMUserService extends ScService
{
    private $model;

    public function __construct(ImUser $model)
    {
        $this->model = $model;
    }

    /**
     * 根据IM标识查询用户
     * @param $identifier
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function find($identifier)
    {
        return $this->model->where('identifier', $identifier)->first();
    }

    /**
     * 根据用户ID查IM用户
     * @param $userId
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function findByUserId($userId)
    {
        return $this->model->where('user_id', $userId)->first();
    }

    /**
     * 添加IM用户
     * @param $userId
     * @param $nick
     * @param $headImg
     * @param $type
     * @param $identifier
     * @return bool
     */
    public function add($userId, $nick, $headImg, $type, $identifier)
    {
        $this->model->user_id = $userId;
        $this->model->nick = $nick;
        $this->model->image = $headImg;
        $this->model->user_type = $type;
        $this->model->identifier = $identifier;
        return $this->model->save();
    }

    /**
     * 更新IM用户
     * @param $identifier
     * @param $data
     * @return int
     */
    public function update($identifier, $data)
    {
        return $this->model->where('identifier', $identifier)->update($data);
    }

    /**
     * 根据userId集查询IM用户
     * @param $userIds
     * @param array $columns
     * @return \Illuminate\Support\Collection
     */
    public function getByUserIds($userIds, $columns = ['*'])
    {
        return $this->model->whereIn('user_id', $userIds)->select($columns)->get();
    }

}
