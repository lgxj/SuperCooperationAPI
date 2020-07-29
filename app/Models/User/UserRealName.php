<?php
namespace App\Models\User;

use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\Log;

class UserRealName extends BaseUser
{
    protected $table = 'user_real_name';

    protected $primaryKey = 'user_id';

    protected $casts = [
        'ocr_order_no' => 'string'
    ];

    protected $fillable = ['user_id', 'name', 'idcard', 'sex', 'nation', 'birth', 'address', 'validDate', 'authority', 'frontPhoto', 'backPhoto', 'headPhoto', 'frontWarnCode', 'backWarnCode', 'input_type', 'ocr_order_no'];

    const INPUT_TYPE_OCR = 1;        // OCR识别
    const INPUT_TYPE_MANUAL = 2;        // 手动录入

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
     * @return bool
     * @throws BusinessException
     */
    public function saveInfo($userId, $data)
    {
        try {
            $info = $this->where('user_id', $userId)->first();
            if ($info) {
                $info->save($data);
            } else {
                $this->create($data);
            }
            return true;
        } catch (\Exception $e) {
            Log::error('保存实名信息失败' . $e->getMessage());
            throw new BusinessException('保存实名信息失败');
        }
    }
}
