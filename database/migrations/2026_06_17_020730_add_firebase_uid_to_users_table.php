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
    Schema::table('users', function (Blueprint $table) {
        // Thêm cột firebase_uid sau cột email (hoặc bất kỳ cột nào bạn muốn)
        // Dùng nullable vì có thể user cũ chưa có, unique để đảm bảo mỗi uid là duy nhất
        $table->string('firebase_uid')->nullable()->unique()->after('email');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        // Xóa cột khi rollback
        $table->dropColumn('firebase_uid');
    });
}
};
