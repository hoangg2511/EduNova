<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();   // 'earning_rate.document_upload', 'spending_rate.document_download', ...
            $table->bigInteger('value');       // số coin
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_configs');
    }
};