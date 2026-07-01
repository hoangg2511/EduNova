<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    protected $fillable = [
        'name', 'description', 'url', 'downloads', 
        'rate', 'medium_rate', 'size','author','status', 'uploaded_by','reviewed_at','rejection_reason'
    ];

    /**
     * The users that are related to this document.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'recent_activities')
            ->withTimestamps();
    }

    /**
     * The users that have saved this document.
     */
    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'my_documents')
            ->using(MyDocument::class)
            ->withTimestamps();
    }

    /**
     * The upload record for this document.
     */
    public function upload(): HasOne
    {
        return $this->hasOne(Upload::class);
    }

    /**
     * The user who uploaded this document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * The types that are related to this document.
     */
    public function types(): BelongsToMany
    {
        return $this->belongsToMany(Type::class, 'document_type')
            ->withTimestamps();
    }

    /**
     * The tags that are related to this document.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'document_tag')
            ->withTimestamps();
    }

    // /**
    //  * The reviews for this document.
    //  */
    public function reviews(): HasMany
{
    return $this->hasMany(\App\Models\DocumentReview::class);
}
    //  public function uploader(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'user_id');
    // }
 
    // public function reviewer(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'reviewed_by');
    // }
 
    // ─── Scopes ───────────────────────────────────────────────────────────────
 
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
 
    public function getTypeAttribute(): ?string
    {
        return $this->types->first()?->name;
    }

    public function getSubjectAttribute(): ?string
    {
        return $this->tags->first()?->name;
    }
 
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
 
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
 
    // ─── Accessors ────────────────────────────────────────────────────────────
 
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
            'pdf'  => 'file-text',
            'docx' => 'file',
            'pptx' => 'presentation',
            'xlsx' => 'table-2',
            default => 'file',
        };
    }
 
    /** Hex color per type */
    public function getColorAttribute(): string
    {
        return match ($this->type) {
            'pdf'  => '#ef4444',
            'docx' => '#3b82f6',
            'pptx' => '#f59e0b',
            'xlsx' => '#10b981',
            default => '#64748b',
        };
    }
 
    // ─── Helpers ──────────────────────────────────────────────────────────────
 
    public function isPending(): bool   { return $this->status === 'pending';  }
    public function isApproved(): bool  { return $this->status === 'approved'; }
    public function isRejected(): bool  { return $this->status === 'rejected'; }
 
    public function approve(int $reviewerId): void
    {
        $this->update([
            'status'      => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);
    }
 
    public function reject(int $reviewerId, ?string $reason = null): void
    {
        $this->update([
            'status'           => 'rejected',
            'reviewed_by'      => $reviewerId,
            'reviewed_at'      => now(),
            'rejection_reason' => $reason,
        ]);
    }

     public function recalcRating(): void
    {
        $avg   = $this->reviews()->avg('rating') ?? 0;
        $count = $this->reviews()->count();
 
        $this->update([
            'rate'        => round($avg, 1),
            'medium_rate' => round($avg, 1),
        ]);
    }
}