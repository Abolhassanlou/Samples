<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            // Only meaningful for type=group — a direct conversation's
            // "title" in the UI falls back to the other participant's
            // name instead (see ChatConversationResource/the frontends).
            $table->string('title')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
