<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Knowledge extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'knowledge';

    protected $fillable = [
        'user_id',
        'title',
        'format',
        'status',
        'data',
        'description',
        'view_count',
        'published_at',
    ];

    protected $casts = [
        'data' => 'array',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the knowledge
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: get published knowledge only
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    /**
     * Scope: get user's drafts
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope: order by most viewed
     */
    public function scopeMostViewed($query)
    {
        return $query->orderBy('view_count', 'desc');
    }

    /**
     * Scope: order by recent
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get knowledge tree data with fallback
     */
    public function getTreeData()
    {
        return $this->data ?? [];
    }

    /**
     * Get main topic name from knowledge tree
     */
    public function getMainTopic(): ?string
    {
        return $this->data['ten_chuyen_de'] ?? $this->title;
    }

    /**
     * Get child topics count
     */
    public function getChildTopicsCount(): int
    {
        return count($this->data['cac_chu_de_con'] ?? []);
    }

    /**
     * Publish the knowledge
     */
    public function publish()
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
        return $this;
    }

    /**
     * Archive the knowledge
     */
    public function archive()
    {
        $this->update([
            'status' => 'archived',
        ]);
        return $this;
    }

    /**
     * Increment view count
     */
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }
}
