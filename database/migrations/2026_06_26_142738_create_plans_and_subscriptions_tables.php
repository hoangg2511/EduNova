<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('key')->unique()->nullable();        // alias dùng trong code
            $table->text('description')->nullable();
            $table->unsignedInteger('price')->default(0);      // VNĐ, integer (không dùng decimal)
            $table->unsignedSmallInteger('duration_days')->default(30); // 0 = vĩnh viễn
            $table->unsignedInteger('token_limit')->default(0);         // 0 = không giới hạn
            $table->unsignedSmallInteger('knowledge_limit')->default(0);
            $table->unsignedSmallInteger('download_limit')->default(0);
            $table->json('features')->nullable();
            $table->string('color', 20)->default('#94a3b8');   // hex color cho UI
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('payment_method', 20)->default('admin'); // sepay|bank|admin
            $table->string('transaction_id', 100)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['plan_id', 'status']);
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};