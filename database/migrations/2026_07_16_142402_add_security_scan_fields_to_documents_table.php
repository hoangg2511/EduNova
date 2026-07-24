<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('scan_status')->default('pending')->after('status'); 
            // pending | passed | flagged | failed
            $table->json('scan_result')->nullable()->after('scan_status');
            $table->longText('extracted_text')->nullable()->after('scan_result');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['scan_status', 'scan_result', 'extracted_text']);
        });
    }
};