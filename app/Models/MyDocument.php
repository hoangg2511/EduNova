<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Casts\Attribute;

class MyDocument extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'my_documents';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Get the user that owns this saved document.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the document that is saved.
     */
    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
