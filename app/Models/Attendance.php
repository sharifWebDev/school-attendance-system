<?php
// app/Models/Attendance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'date',
        'status',
        'note',
        'recorded_by'
    ];

    protected $casts = [
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeForMonth($query, string $month)
    {
        return $query->whereYear('date', substr($month, 0, 4))
                    ->whereMonth('date', substr($month, 5, 2));
    }

    public function scopeForClass($query, string $class)
    {
        return $query->whereHas('student', function ($q) use ($class) {
            $q->where('class', $class);
        });
    }
}
