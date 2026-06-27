<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_add_uploaded_by_to_documents_table.php
public function up(): void
{
    Schema::table('documents', function (Blueprint $table) {
        $table->foreignId('uploaded_by')
              ->nullable()
              ->after('status')
              ->constrained('users')
              ->nullOnDelete();
    });
}

public function down(): void
{
    Schema::table('documents', function (Blueprint $table) {
        $table->dropForeignIdFor('uploaded_by');
        $table->dropColumn('uploaded_by');
    });
}
};
