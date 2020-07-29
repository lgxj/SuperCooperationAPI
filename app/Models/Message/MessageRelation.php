<?php
namespace App\Models\Message;

class MessageRelation extends BaseMessage
{
    protected $table = 'message_relation';

    protected $primaryKey = 'rid';

    /**
     * 所属消息主体
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function msg()
    {
        return $this->belongsTo(Message::class, 'message_id', 'mid');
    }

}
