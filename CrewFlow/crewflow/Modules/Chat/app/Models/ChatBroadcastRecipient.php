<?php

namespace Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Models\User;

class ChatBroadcastRecipient extends Model
{
    protected $table = 'chat_broadcast_recipients';

    protected $fillable = [
        'broadcast_id',
        'user_id',
        'conversation_id',
    ];

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(ChatBroadcast::class, 'broadcast_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }
}
