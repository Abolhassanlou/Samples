<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two new tables backing the "Broadcast history" feature — separate
 * from the regular chat/conversation tables, since a broadcast fans out
 * into ordinary private `direct` conversations (see
 * ChatController::broadcast()) that also carry unrelated day-to-day
 * messages before and after. Without this, there was no way to answer
 * "who did I send THIS particular announcement to, and who's replied
 * since" — the conversation itself mixes broadcast replies with
 * everything else ever said in it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('chat_broadcast_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broadcast_id')->constrained('chat_broadcasts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // The specific direct conversation this recipient's copy of
            // the broadcast landed in — "has this person replied?" is
            // answered by checking for a message from them in this
            // conversation created after the broadcast's own timestamp,
            // not by anything stored on this row itself (a reply is
            // just an ordinary ChatMessage; no separate "read/replied"
            // flag to keep in sync).
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['broadcast_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_broadcast_recipients');
        Schema::dropIfExists('chat_broadcasts');
    }
};
