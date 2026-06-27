<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ExamAttempt;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'security_id',
        'title',
        'description',
        'duration',
        'passMark',
        'maxAttempts',
        'status',
        'shuffle',
        'shuffleOptions',
        'showResult',
        'requireName',
    ];

    protected $casts = [
        'shuffle' => 'boolean',
        'shuffleOptions' => 'boolean',
        'showResult' => 'boolean',
        'requireName' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function security()
    {
        return $this->belongsTo(Security::class);
    }

    public function questions()
    {
       return $this->hasMany(Question::class, 'examId', 'id');
    }

    public function attempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }
}
