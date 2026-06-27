<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLog extends Model
{
    protected $table = 'user_logs';

    protected $fillable = [
        'user_id',
        'token_limit',
        'duration_days',
        'knowledge_limit',
        'download_limit',
    ];

    protected $casts = [
        'token_limit' => 'integer',
        'duration_days' => 'integer',
        'knowledge_limit' => 'integer',
        'download_limit' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
