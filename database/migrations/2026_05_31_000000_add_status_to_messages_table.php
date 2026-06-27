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
        Schema::table('messages', function (Blueprint $table) {
            $table->enum('status', ['active', 'deleted'])->default('active')->after('content');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Drop index first if exists
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = array_map(fn($idx) => $idx->getName(), $sm->listTableIndexes('messages'));
            if (in_array('messages_status_index', $indexes, true)) {
                $table->dropIndex('messages_status_index');
            } elseif (in_array('status', $indexes, true)) {
                $table->dropIndex(['status']);
            }

            if (Schema::hasColumn('messages', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
