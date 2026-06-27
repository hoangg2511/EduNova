<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Sửa bảng 'exams'
        Schema::table('exams', function (Blueprint $table) {
            $table->renameColumn('name', 'title');
        });

        // 2. Sửa bảng 'questions'
        Schema::table('questions', function (Blueprint $table) {
            $table->renameColumn('exam_id', 'examId');
            $table->renameColumn('correct_answers', 'correctAnswers');
        });
    }

    public function down()
    {
        // Hoàn tác (Rollback)
        Schema::table('exams', function (Blueprint $table) {
            $table->renameColumn('title', 'name');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->renameColumn('examId', 'exam_id');
            $table->renameColumn('correctAnswers', 'correct_answers');
        });
    }
};