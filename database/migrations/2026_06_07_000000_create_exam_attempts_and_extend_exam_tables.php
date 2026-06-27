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
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('candidate_name')->nullable();
            $table->integer('score')->default(0);
            $table->integer('correct')->default(0);
            $table->integer('total_questions')->default(0);
            $table->boolean('passed')->default(false);
            $table->integer('time_taken_seconds')->nullable();
            $table->json('answers')->nullable();
            $table->timestamps();
        });

        Schema::table('exams', function (Blueprint $table) {
            if (!Schema::hasColumn('exams', 'requireName')) {
                $table->boolean('requireName')->default(false)->after('showResult');
            }
        });

        Schema::table('securities', function (Blueprint $table) {
            if (!Schema::hasColumn('securities', 'useAccessKey')) {
                $table->boolean('useAccessKey')->default(false)->after('maxTabWarnings');
            }
            if (!Schema::hasColumn('securities', 'accessKey')) {
                $table->string('accessKey')->nullable()->after('useAccessKey');
            }
        });

        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'correct_answers')) {
                $table->json('correct_answers')->nullable()->after('options');
            }
            if (!Schema::hasColumn('questions', 'explanation')) {
                $table->text('explanation')->nullable()->after('correct_answers');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'explanation')) {
                $table->dropColumn('explanation');
            }
            if (Schema::hasColumn('questions', 'correct_answers')) {
                $table->dropColumn('correct_answers');
            }
        });

        Schema::table('securities', function (Blueprint $table) {
            if (Schema::hasColumn('securities', 'accessKey')) {
                $table->dropColumn('accessKey');
            }
            if (Schema::hasColumn('securities', 'useAccessKey')) {
                $table->dropColumn('useAccessKey');
            }
        });

        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'requireName')) {
                $table->dropColumn('requireName');
            }
        });

        Schema::dropIfExists('exam_attempts');
    }
};
