<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            // A real foreign key to Shift's `events` table, nullOnDelete
            // — so deleting an Event doesn't take its team chat down
            // with it, it just stops being linked to one. (An earlier
            // draft of this file's docblock reasoned this should be a
            // plain unconstrained column for cross-module independence
            // — that reasoning was wrong for what's actually deployed;
            // Chat does have a hard DB-level dependency on Shift's
            // `events` table existing, matching this module's README,
            // which already lists Shift as a dependency to install
            // first.)
            $table->foreignId('event_id')->nullable()->after('title')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_id');
        });
    }
};
