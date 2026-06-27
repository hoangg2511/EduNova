<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Type extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * The documents that are related to this type.
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_type')
            ->withTimestamps();
    }
}