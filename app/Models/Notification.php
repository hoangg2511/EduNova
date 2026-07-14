<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Notification extends Model
{
    use HasFactory;

    // ── Danh sách các loại thông báo ────────────────────────────────
    // Mỗi khi thêm 1 loại thông báo mới, khai báo hằng số ở đây trước,
    // tránh gõ tay chuỗi 'type' rải rác khắp code (dễ gõ sai, khó tra cứu).
    public const TYPE_SCHEDULE_REMINDER = 'schedule_reminder';
    public const TYPE_EXAM_REMINDER     = 'exam_reminder';
    public const TYPE_PLAN_EXPIRING     = 'plan_expiring';
    public const TYPE_PAYMENT_COMPLETED = 'payment_completed';
    public const TYPE_DOCUMENT_APPROVED = 'document_approved';
    public const TYPE_DOCUMENT_REJECTED = 'document_rejected';
    public const TYPE_SYSTEM            = 'system';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data'    => 'array',
        'read_at' => 'datetime',
    ];

    // ── Relationships ───────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }

    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }
}