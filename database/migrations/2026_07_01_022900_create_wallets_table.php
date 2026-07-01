<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->bigInteger('balance')->default(0); // số nguyên, tránh sai số thập phân
            $table->string('currency_code', 20)->default('COIN'); // COIN, POINT, CREDIT...
            $table->enum('status', ['active', 'locked'])->default('active');
            $table->timestamps();

            // Mỗi user chỉ có 1 ví cho mỗi loại tiền tệ
            $table->unique(['user_id', 'currency_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};