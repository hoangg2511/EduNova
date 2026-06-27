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
        Schema::table('exam_attempts', function (Blueprint $table) {
        // Bước 1: Xóa khóa ngoại (tên khóa ngoại thường là 'tên_bảng_tên_cột_foreign')
        // Lưu ý: Bạn cần thay 'exam_attempts_user_id_foreign' bằng tên thực tế của khóa ngoại trong DB của bạn
        $table->dropForeign(['user_id']); 
        
        // Bước 2: Xóa cột
        $table->dropColumn('user_id');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_attempts', 'user_id')) {
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
            }
        });
    }
};
