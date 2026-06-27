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
        Schema::table('documents', function (Blueprint $table) {
            // reviewed_at dùng để lưu thời điểm được xét duyệt
            $table->timestamp('reviewed_at')->nullable()->after('status');
            
            // rejection_reason dùng để lưu lý do nếu bị từ chối
            $table->text('rejection_reason')->nullable()->after('reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['reviewed_at', 'rejection_reason']);
        });
    }
};