<?php

namespace Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Authentication\Models\User;

/**
 * `type` is 'direct' (exactly two participants), 'group' (three or more,
 * with an optional `title`), or 'broadcast' — note "broadcast" is never
 * actually stored as a conversation type here: a broadcast is implemented
 * as fanning the same message out into several ordinary 'direct'
 * conversations (one per recipient), so each worker only ever sees their
 * own private reply thread with the sender — see
 * ChatController::broadcast().
 *
 * `event_id` (nullable) links a group conversation to a Shift-module
 * Event — set automatically by AssignmentObserver when workers are
 * assigned to that event's shifts, so a team chat exists without any
 * admin having to create it by hand.
 */
class ChatConversation extends Model
{
    protected $table = 'chat_conversations';

    protected $fillable = [
        'type',
        'title',
        'event_id',
    ];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_participants', 'conversation_id', 'user_id')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasMany
    {
        return $this->messages()->latest('created_at');
    }
}
