<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Services\SupabaseService;
use App\Models\NewsTag;
use App\Models\User;
class NewsArticle extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content',
        'category', 'emoji', 'author_name', 'author_initials',
        'read_time', 'views', 'is_featured',
        'status', 'published_at', 'scheduled_at','thumbnail_url'
    ];

    protected $casts = [
        'is_featured'  => 'boolean',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'views'        => 'integer',
        'read_time'    => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(NewsTag::class, 'news_article_tag', 'article_id', 'tag_id')
                    ->withTimestamps();
    }

    public function bookmarkedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'news_bookmarks', 'article_id', 'user_id')
                    ->withTimestamps();
    }

    // ── Scopes ──────────────────────────────────────────────────

    public function scopeDueToPublish(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')
                    ->whereNotNull('scheduled_at')
                    ->where('scheduled_at', '<=', now());
    }

    public static function publishDueScheduled(): int
    {
        $count = 0;
        static::dueToPublish()->get()->each(function (self $article) use (&$count) {
            $article->update([
                'status'       => 'published',
                'published_at' => $article->published_at ?? $article->scheduled_at,
            ]);
            $count++;
        });
        return $count;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    public function scopeOfCategory(Builder $query, ?string $cat): Builder
    {
        return $cat ? $query->where('category', $cat) : $query;
    }

    public function scopeOfStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeSearch(Builder $query, ?string $q): Builder
    {
        return $q
            ? $query->where(fn ($s) =>
                $s->where('title', 'like', "%{$q}%")
                  ->orWhere('excerpt', 'like', "%{$q}%")
              )
            : $query;
    }

    // ── Accessors ───────────────────────────────────────────────

    public function getFormattedDateAttribute(): string
    {
        return ($this->published_at ?? $this->created_at)->format('d/m/Y');
    }

    public function getFormattedViewsAttribute(): string
    {
        return $this->views >= 1000
            ? number_format($this->views / 1000, 1) . 'K'
            : (string) $this->views;
    }

    public function getThumbnailUrl(SupabaseService $supabase): ?string
    {
        if (! $this->thumbnail_path) {
            return null;
        }
 
        return $supabase->getPublicUrl('documents', $this->thumbnail_path);
    }


    // ── Helpers ─────────────────────────────────────────────────

    public static function generateSlug(string $title): string
    {
        $base  = Str::slug($title);
        $count = static::where('slug', 'like', $base . '%')->count();
        return $count ? $base . '-' . ($count + 1) : $base;
    }

    public function incrementViews(): void
    {
        $this->increment('views');
    }

    public function publish(): void
    {
        $this->update([
            'status'       => 'published',
            'published_at' => $this->published_at ?? now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update(['status' => 'draft']);
    }

    public function syncTagNames(array $names): void
    {
        $ids = collect($names)->map(fn ($n) =>
            NewsTag::firstOrCreate(['name' => $n])->id
        );
        $this->tags()->sync($ids);
    }
}