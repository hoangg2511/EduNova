<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\TypeEvent;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';

    protected $fillable = [
        'user_id',
        'type_event_id',
        'title',
        'date',
        'start',
        'end',
        'note',
        'status',
        'repeat_type', 'repeat_end_date', 'repeat_group_id',
    ];

    protected $casts = [
        'date' => 'date',
        'repeat_end_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeEvent(): BelongsTo
    {
        return $this->belongsTo(TypeEvent::class);
    }
}
