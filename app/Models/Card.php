<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'deck_id',
        'front',
        'back',
        'difficulty',
        'status',
        'starred',
        'review_count',
        'flipped',
        'hint',
    ];

    protected $casts = [
        'starred' => 'boolean',
        'flipped' => 'boolean',
        'review_count' => 'integer',
    ];

    /**
     * Get the deck that owns the card.
     */
    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class);
    }
}
