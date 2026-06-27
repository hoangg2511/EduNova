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
    Schema::create('documents', function (Blueprint $table) {
            $table->id(); // Tự động tạo 'id' là BIGINT, PK, Auto Increment
            $table->string('name');
            $table->text('description'); // Đã sửa lỗi chính tả
            $table->string('url');
            $table->integer('downloads')->default(0); // Đã sửa lỗi chính tả
            $table->float('rate')->default(0);
            $table->float('medium_rate')->default(0);
            $table->string('size');
            $table->timestamps(); // Tự động tạo 'created_at' và 'updated_at'
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
