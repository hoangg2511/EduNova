<?php
namespace App\Models;

use App\Jobs\GenerateDocumentEmbeddings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Jobs\GenerateRelatedDocuments;
use Illuminate\Support\Facades\DB;
class Document extends Model
{
    protected $fillable = [
        'name', 'description', 'url', 'downloads', 'views',
        'rate', 'medium_rate', 'size', 'author', 'status', 'uploaded_by', 'reviewed_at', 'rejection_reason',
        'scan_status', 'scan_result', 'extracted_text',
    ];

    protected $casts = [
        'scan_result'     => 'array',
        'extracted_text'  => 'string',
    ];

    // ─── Relations ──────────────────────────────────────────────────────────

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'recent_activities')->withTimestamps();
    }

    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'my_documents')
            ->using(MyDocument::class)
            ->withTimestamps();
    }

    public function upload(): HasOne
    {
        return $this->hasOne(Upload::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function types(): BelongsToMany
    {
        return $this->belongsToMany(Type::class, 'document_type')->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'document_tag')->withTimestamps();
    }

    public function embeddings(): HasMany
    {
        return $this->hasMany(DocumentEmbedding::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DocumentReview::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeSearch($query, ?string $q)
    {
        return $q
            ? $query->where(fn ($s) =>
                $s->where('name', 'like', "%{$q}%")
                  ->orWhereHas('uploader', fn ($u) => $u->where('name', 'like', "%{$q}%"))
              )
            : $query;
    }

    public function scopeOfType($query, ?string $type)
    {
        return $type ? $query->whereHas('types', fn ($q) => $q->where('name', $type)) : $query;
    }

    public function scopeOfSubject($query, ?string $subject)
    {
        return $subject ? $query->whereHas('tags', fn ($q) => $q->where('name', $subject)) : $query;
    }

    public function scopeOfStatus($query, ?string $status)
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // ─── Accessors ──────────────────────────────────────────────────────────

    public function getTypeAttribute(): ?string
    {
        return $this->types->first()?->name;
    }

    public function getSubjectAttribute(): ?string
    {
        return $this->tags->first()?->name;
    }

    /** dd/mm/yyyy */
    public function getUploadDateAttribute(): string
    {
        return $this->created_at->format('d/m/Y');
    }

    /** Author name from relationship */
    public function getAuthorAttribute(): string
    {
        return $this->uploader?->name ?? 'Ẩn danh';
    }

    /** Icon name for Lucide */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            'pdf'   => 'file-text',
            'docx'  => 'file',
            'pptx'  => 'presentation',
            'xlsx'  => 'table-2',
            default => 'file',
        };
    }

    /** Hex color per type */
    public function getColorAttribute(): string
    {
        return match ($this->type) {
            'pdf'   => '#ef4444',
            'docx'  => '#3b82f6',
            'pptx'  => '#f59e0b',
            'xlsx'  => '#10b981',
            default => '#64748b',
        };
    }

    // ─── State helpers ──────────────────────────────────────────────────────

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isRejected(): bool { return $this->status === 'rejected'; }
    public function isHidden(): bool   { return $this->status === 'hidden'; }

    // ─── Helper dùng chung để dọn dữ liệu RAG ──────────────────────────────

    private function purgeEmbeddings(): void
    {
        $this->embeddings()->delete();

        RelatedDocument::where('document_id', $this->id)
            ->orWhere('related_document_id', $this->id)
            ->delete();
    }

    // ─── State transitions (chỉ 1 bản duy nhất cho mỗi method) ─────────────

    public function hide(): void
    {
        $this->update(['status' => 'hidden']);

        // Tài liệu bị ẩn không nên còn xuất hiện trong gợi ý cho user khác
        $this->purgeEmbeddings();
    }

    public function unhide(): void
    {
        $this->update(['status' => 'approved']);

        // Cần sinh lại embedding + related vì cả 2 đã bị xoá lúc hide()
        $this->dispatchEmbeddingPipeline();
    }

    public function approve(int $reviewerId): void
    {
        $this->update([
            'status'      => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        $this->dispatchEmbeddingPipeline();
    }

    /**
     * Chain: sinh embedding xong mới tính related documents (cần embedding có sẵn).
     * Dùng chung cho mọi trường hợp document chuyển sang 'approved' (approve() lẫn unhide()).
     */
    private function dispatchEmbeddingPipeline(): void
    {
        DB::afterCommit(function () {
            \Illuminate\Support\Facades\Bus::chain([
                new GenerateDocumentEmbeddings($this->id),
                new GenerateRelatedDocuments($this->id),
            ])->onQueue('embeddings')->dispatch(); // ← thêm onQueue()
        });
    }

    public function reject(int $reviewerId, ?string $reason = null): void
    {
        $this->update([
            'status'           => 'rejected',
            'reviewed_by'      => $reviewerId,
            'reviewed_at'      => now(),
            'rejection_reason' => $reason,
        ]);

        // Xử lý trường hợp tài liệu từng approved (có embedding) rồi bị admin đổi ý sang reject
        $this->purgeEmbeddings();
    }

    public function recalcRating(): void
    {
        $avg = $this->reviews()->avg('rating') ?? 0;

        $this->update([
            'rate'        => round($avg, 1),
            'medium_rate' => round($avg, 1),
        ]);
    }
}