<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name', 'key', 'slug', 'description', 'price', 'duration_days',
        'token_limit', 'knowledge_limit', 'download_limit',
        'features', 'color', 'is_featured', 'is_active',
    ];

    protected $casts = [
        'features'        => 'array',
        'is_featured'     => 'boolean',
        'is_active'       => 'boolean',
        'price'           => 'integer',
        'duration_days'   => 'integer',
        'token_limit'     => 'integer',
        'knowledge_limit' => 'integer',
        'download_limit'  => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────────────

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->where('status', 'active');
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function isFree(): bool
    {
        return $this->price == 0;
    }

    public static function defaultPlan(): self
    {
        return self::where('slug', 'free')->firstOrFail();
    }

    public function formattedPrice(): string
    {
        return $this->isFree()
            ? 'Miễn phí'
            : number_format($this->price, 0, ',', '.') . 'đ/tháng';
    }
}