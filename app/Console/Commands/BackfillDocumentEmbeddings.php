<?php

namespace App\Console\Commands;

use App\Jobs\GenerateDocumentEmbeddings;
use App\Models\Document;
use Illuminate\Console\Command;

class BackfillDocumentEmbeddings extends Command
{
    protected $signature = 'documents:backfill-embeddings';
    protected $description = 'Sinh embedding cho các tài liệu approved chưa có embedding';

    public function handle(): int
    {
        $documents = Document::where('status', 'approved')
            ->whereDoesntHave('embeddings')
            ->whereNotNull('extracted_text')
            ->get();

        $this->info("Tìm thấy {$documents->count()} tài liệu cần backfill.");

        $bar = $this->output->createProgressBar($documents->count());
        foreach ($documents as $doc) {
            GenerateDocumentEmbeddings::dispatch($doc->id)->onQueue('embeddings'); // ← thêm .onQueue('embeddings')
            $bar->advance();
            usleep(300_000); // giãn cách nhẹ, tránh rate limit Gemini API khi dispatch hàng loạt
        }
        $bar->finish();

        return self::SUCCESS;
    }
}