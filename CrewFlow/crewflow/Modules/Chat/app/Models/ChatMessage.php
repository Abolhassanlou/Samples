<?php

namespace Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Authentication\Models\User;

class ChatMessage extends Model
{
    protected $table = 'chat_messages';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'broadcast_id',
        'message',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Null for an ordinary message either side typed directly — set
     * only on a recipient's copy of a broadcast (see
     * ChatController::broadcast()). Drives the 30-day rolling archive
     * in messages() — see that method's docblock.
     */
    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(ChatBroadcast::class, 'broadcast_id');
    }
}
