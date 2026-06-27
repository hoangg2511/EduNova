<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kiểm tra xem cột 'status' đã tồn tại trong bảng 'decks' chưa
        if (!Schema::hasColumn('decks', 'status')) {
            Schema::table('decks', function (Blueprint $table) {
                // Thêm cột status, mặc định là 'active'
                $table->string('status')->default('active')->after('name');
            });
        }
    }

    public function down(): void
    {
        // Xóa cột nếu cần rollback
        if (Schema::hasColumn('decks', 'status')) {
            Schema::table('decks', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
