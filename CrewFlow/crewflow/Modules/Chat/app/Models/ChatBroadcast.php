<?php

namespace Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Authentication\Models\User;

/**
 * One row per POST /api/chats/broadcast call — the original message and
 * when it was sent, independent of the (possibly much longer-running)
 * direct conversations it fanned out into. See
 * ChatController::broadcastsIndex()/broadcastShow() for how this powers
 * the "Broadcast history" admin view, and the migration's docblock for
 * why this exists as its own table rather than trying to tag messages
 * within the conversations themselves.
 */
class ChatBroadcast extends Model
{
    protected $table = 'chat_broadcasts';

    protected $fillable = [
        'sender_id',
        'message',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(ChatBroadcastRecipient::class, 'broadcast_id');
    }
}
