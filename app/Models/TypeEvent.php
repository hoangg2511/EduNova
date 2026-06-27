<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Event;

class TypeEvent extends Model
{
    use HasFactory;

    protected $table = 'type_events';

    protected $fillable = [
        'label',
        'key',
        'color',
        'visible',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'visible' => 'boolean',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
