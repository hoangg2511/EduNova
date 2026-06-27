<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Security extends Model
{
    protected $table = 'security';
    use HasFactory;

    protected $fillable = [
        'noTab', 'noCopy', 'noRightClick', 'fullRandom', 'forceFullscreen', 'maxTabWarnings', 'useAccessKey', 'accessKey',
    ];

    protected $casts = [
        'noTab' => 'boolean',
        'noCopy' => 'boolean',
        'noRightClick' => 'boolean',
        'fullRandom' => 'boolean',
        'forceFullscreen' => 'boolean',
        'useAccessKey' => 'boolean',
    ];

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }
}
