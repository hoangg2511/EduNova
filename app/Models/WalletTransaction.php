<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'user_id',
        'amount',
        'balance_after',
        'type',
        'reference_type',
        'reference_id',
        'description',
        'performed_by',
    ];

    protected $casts = [
        'amount'        => 'integer',
        'balance_after' => 'integer',
    ];

    public const TYPE_EARN         = 'earn';
    public const TYPE_SPEND        = 'spend';
    public const TYPE_REFUND       = 'refund';
    public const TYPE_ADMIN_ADJUST = 'admin_adjust';

    // ─── Relationships ────────────────────────────────────────────────────────

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Polymorphic relation tới đối tượng liên quan (Document, Exam, ...).
     * Lưu ý: dùng MorphTo thủ công vì cột không theo chuẩn *_type/*_id mặc định
     * (đã đặt tên đúng chuẩn reference_type/reference_id nên hoạt động bình thường).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeEarn($query)
    {
        return $query->where('type', self::TYPE_EARN);
    }

    public function scopeSpend($query)
    {
        return $query->where('type', self::TYPE_SPEND);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    public function isDebit(): bool
    {
        return $this->amount < 0;
    }
}