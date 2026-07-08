<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WalletConfig extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description',
        'is_active',
    ];

    protected $casts = [
        'value'     => 'integer',
        'is_active' => 'boolean',
    ];

    private const CACHE_PREFIX = 'wallet_config:';
    private const CACHE_TTL    = 3600; // 1 giờ

    // ─── Static helpers ───────────────────────────────────────────────────────

    /**
     * Lấy giá trị config theo key, có cache.
     * Ví dụ: WalletConfig::get('earning_rate.document_upload', 0)
     */
    public static function get(string $key, int $default = 0): int
    {
        return Cache::remember(self::CACHE_PREFIX . $key, self::CACHE_TTL, function () use ($key, $default) {
            $config = static::where('key', $key)->where('is_active', true)->first();
            return $config?->value ?? $default;
        });
    }

    /**
     * Cập nhật giá trị config và xóa cache tương ứng.
     */
    public static function set(string $key, int $value, ?string $description = null): self
    {
        $config = static::updateOrCreate(
            ['key' => $key],
            array_filter([
                'value'       => $value,
                'description' => $description,
            ], fn($v) => $v !== null)
        );

        Cache::forget(self::CACHE_PREFIX . $key);

        return $config;
    }

    protected static function booted(): void
    {
        // Tự xóa cache mỗi khi config thay đổi hoặc bị xóa
        static::saved(fn (self $config) => Cache::forget(self::CACHE_PREFIX . $config->key));
        static::deleted(fn (self $config) => Cache::forget(self::CACHE_PREFIX . $config->key));
    }
}