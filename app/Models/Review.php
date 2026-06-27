<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'comment',
        'user_id',
        'document_id',
    ];

    /**
     * Review belongs to a single user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Review belongs to a single document.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
