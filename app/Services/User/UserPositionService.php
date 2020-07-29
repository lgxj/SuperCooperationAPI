<?php


namespace App\Services\User;


use App\Bridges\User\UserBridge;
use App\Consts\UserConst;
use App\Models\User\UserAddressPosition;
use App\Services\ScService;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 个人定位服务层
 *
 * Class UserPositionService
 * @package App\Services\User
 */
class UserPositionService extends ScService
{
    public function search($filter = [], $pageSize = 10, $orderColumn = 'updated_at', $direction = 'desc')
    {
        /** @var UserAddressPosition $userPostionModel */
        $userPositionModel = UserAddressPosition::getModel();
        $userIds = [];
        $userBridge = $this->getUserBridge();
        if (!empty($filter['user_id'])) {
            $userGrant = $userBridge->findByGrantType(UserConst::GRANT_LOGIN_TYPE_PHONE, $filter['user_id']);
            if ($userGrant) {
                $userIds[] = $userGrant['user_id'];
            }
            if (empty($userIds)) {
                $users = $userBridge->search(['user_name' => $filter['user_id']]);
                $userIds = array_keys($users);
            }

            if (empty($userIds)) {
                return new LengthAwarePaginator([], 0, $pageSize);
            }
        }

        $userPositionModel = $userPositionModel->when(!empty($userIds), function ($query) use ($userIds) {
            $query->whereIn('user_id', $userIds);
        })->when(!empty($filter['updated_at']),function ($query) use($filter){
            $query->where('updated_at','>=',$filter['updated_at'][0])->where('updated_at','<=',$filter['updated_at'][1]);
        });

        $result = $userPositionModel->orderBy($orderColumn, $direction)->paginate($pageSize);
        $tasks = collect($result->items());
        $userIds = array_unique($tasks->pluck('user_id')->toArray());
        $users = $userBridge->users($userIds);

        collect($result->items())->map(function ($item) use($users) {
            $user = $users[$item['user_id']] ?? [];
            $item['user_avatar'] = $user['user_avatar'];
            $item['user_name'] = $user['user_name'];
            return $item;
        });
        return $result;
    }

    /**
     * @return UserService
     */
    protected function getUserBridge(){
        return new UserBridge(new UserService());
    }
}
