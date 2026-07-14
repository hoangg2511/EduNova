<?php
// database/migrations/xxxx_xx_xx_add_repeat_fields_to_events_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('repeat_type')->nullable()->after('type_event_id'); // none|daily|weekly|monthly
            $table->date('repeat_end_date')->nullable()->after('repeat_type');
            $table->uuid('repeat_group_id')->nullable()->after('repeat_end_date');
            $table->index('repeat_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['repeat_type', 'repeat_end_date', 'repeat_group_id']);
        });
    }
};