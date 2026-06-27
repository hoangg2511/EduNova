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
        Schema::table('type_events', function (Blueprint $table) {
            $table->renameColumn('name', 'label');
            $table->string('key')->after('label');
            $table->string('color')->nullable()->after('key');
            $table->boolean('visible')->default(true)->after('color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('type_events', function (Blueprint $table) {
            $table->dropColumn(['key', 'color', 'visible']);
            $table->renameColumn('label', 'name');
        });
    }
};
