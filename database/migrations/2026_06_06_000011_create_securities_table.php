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
        Schema::create('securities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('noTab')->default(true);
            $table->boolean('noCopy')->default(true);
            $table->boolean('noRightClick')->default(true);
            $table->boolean('fullRandom')->default(false);
            $table->boolean('forceFullscreen')->default(false);
            $table->integer('maxTabWarnings')->default(3);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('securities');
    }
};
