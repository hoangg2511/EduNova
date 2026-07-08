<?php
// app/Console/Commands/PublishScheduledNews.php

namespace App\Console\Commands;

use App\Models\NewsArticle;
use Illuminate\Console\Command;

class PublishScheduledNews extends Command
{
    protected $signature = 'news:publish-scheduled';
    protected $description = 'Tự động đăng các bài viết tin tức đã lên lịch khi đến giờ';

    public function handle(): int
    {
        $count = NewsArticle::publishDueScheduled();

        $this->info($count > 0
            ? "Đã tự động đăng {$count} bài viết."
            : 'Không có bài viết nào cần đăng.');

        return self::SUCCESS;
    }
}