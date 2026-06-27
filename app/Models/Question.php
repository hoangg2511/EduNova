<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'examId', 'text', 'type', 'points', 'options', 'correctAnswers', 'explanation',
    ];

    protected $casts = [
        'options' => 'array',
        'correctAnswers' => 'array',
    ];

    public function exam()
    {
       return $this->belongsTo(Exam::class, 'examId', 'id');    
    }
}
