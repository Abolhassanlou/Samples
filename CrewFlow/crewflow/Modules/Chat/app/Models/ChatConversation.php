<?php

namespace Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Authentication\Models\User;

/**
 * MVP scope: `type` is always 'direct' right now — exactly two
 * participants. `group` and `broadcast` (from the original design) are
 * deferred; the schema already allows for them later without a migration
 * change (just a different `type` value and more than 2 participants).
 */
class ChatConversation extends Model
{
    protected $table = 'chat_conversations';

    protected $fillable = [
        'type',
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
