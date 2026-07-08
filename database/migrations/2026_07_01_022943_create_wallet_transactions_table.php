<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')
                  ->constrained('wallets')
                  ->onDelete('cascade');
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->bigInteger('amount'); // +50 hoặc -20
            $table->bigInteger('balance_after'); // số dư sau giao dịch — phục vụ đối soát

            $table->enum('type', ['earn', 'spend', 'refund', 'admin_adjust']);

            // Polymorphic reference: Document, Exam, Deck, ...
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->string('description')->nullable();

            // Admin nào thực hiện điều chỉnh thủ công (nếu type = admin_adjust)
            $table->foreignId('performed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};