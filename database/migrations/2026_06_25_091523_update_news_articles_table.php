<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Cập nhật bảng news_articles ───────────────────────────────
        Schema::table('news_articles', function (Blueprint $table) {
            // content: cho phép null (blade có thể save bài trống khi mới tạo)
            $table->longText('content')->nullable()->change();

            // emoji: tăng độ dài để chứa tên icon lucide (vd: "book-open")
            $table->string('emoji', 50)->default('newspaper')->change();

            // scheduled_at: lên lịch đăng riêng biệt với published_at
            $table->timestamp('scheduled_at')->nullable()->after('published_at');
        });

        // status: thêm 'scheduled' vào ENUM
        // Blueprint->change() không hỗ trợ thay đổi ENUM trên MySQL → dùng raw
        \DB::statement(
            "ALTER TABLE news_articles
             MODIFY COLUMN status ENUM('draft','published','archived','scheduled')
             NOT NULL DEFAULT 'draft'"
        );

        // ── 2. Bảng tags ──────────────────────────────────────────────────
        if (! Schema::hasTable('news_tags')) {
            Schema::create('news_tags', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        // ── 3. Pivot article ↔ tag ────────────────────────────────────────
        if (! Schema::hasTable('news_article_tag')) {
            Schema::create('news_article_tag', function (Blueprint $table) {
                $table->foreignId('article_id')
                      ->constrained('news_articles')
                      ->cascadeOnDelete();
                $table->foreignId('tag_id')
                      ->constrained('news_tags')
                      ->cascadeOnDelete();
                $table->primary(['article_id', 'tag_id']);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('news_article_tag');
        Schema::dropIfExists('news_tags');

        Schema::table('news_articles', function (Blueprint $table) {
            $table->dropColumn('scheduled_at');
            $table->longText('content')->nullable(false)->change();
            $table->string('emoji', 10)->default('📰')->change();
        });

        \DB::statement(
            "ALTER TABLE news_articles
             MODIFY COLUMN status ENUM('draft','published','archived')
             NOT NULL DEFAULT 'draft'"
        );
    }
};