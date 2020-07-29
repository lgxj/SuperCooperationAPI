<?php
namespace App\Models\User;

use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\Log;

class UserFace extends BaseUser
{
    protected $table = 'user_face';

    protected $primaryKey = 'face_id';

    protected $casts = [
        'business_no' => 'string',
        'face_order_no' => 'string',
    ];

    protected $fillable = ['user_id', 'liveRate', 'similarity', 'photo', 'business_type', 'business_no'];

    /**
     * 关联用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * 保存身份信息
     * @param $userId
     * @param $data
     * @param $businessType
     * @param $businessNo
     * @return bool
     * @throws BusinessException
     */
    public function saveInfo($userId, $businessType, $businessNo, $data)
    {
        try {
            $info = $this->where('user_id', $userId)->where('business_type', $businessType)->where('business_no', $businessNo)->first();
            if ($info) {
                $info->save($data);
            } else {
                $this->create($data);
            }
            return true;
        } catch (\Exception $e) {
            Log::error('保存实名信息失败' . $e->getMessage());
            throw new BusinessException('保存身份信息失败');
        }
    }
}
