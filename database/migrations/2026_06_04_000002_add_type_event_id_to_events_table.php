<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('type_event_id')->nullable()->constrained('type_events')->nullOnDelete()->after('user_id');
            $table->index('type_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['type_event_id']);
            $table->dropIndex(['type_event_id']);
            $table->dropColumn('type_event_id');
        });
    }
};
