<?php
// app/Models/Student.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'name',
        'class',
        'section',
        'photo'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function getMonthlyAttendancePercentage(string $month): float
    {
        $totalDays = $this->attendances()
            ->whereYear('date', substr($month, 0, 4))
            ->whereMonth('date', substr($month, 5, 2))
            ->count();

        $presentDays = $this->attendances()
            ->whereYear('date', substr($month, 0, 4))
            ->whereMonth('date', substr($month, 5, 2))
            ->where('status', 'present')
            ->count();

        return $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
    }

    public function getTodayAttendance(): ?Attendance
    {
        return $this->attendances()
            ->whereDate('date', now()->toDateString())
            ->first();
    }
}
