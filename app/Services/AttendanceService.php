<?php
// app/Services/AttendanceService.php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;
use App\Events\AttendanceRecorded;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AttendanceService
{
    public function recordBulkAttendance(array $attendanceData, int $recordedBy): array
    {
        $results = [];

        DB::transaction(function () use ($attendanceData, $recordedBy, &$results) {
            foreach ($attendanceData as $data) {
                $attendance = Attendance::updateOrCreate(
                    [
                        'student_id' => $data['student_id'],
                        'date' => $data['date']
                    ],
                    [
                        'status' => $data['status'],
                        'note' => $data['note'] ?? null,
                        'recorded_by' => $recordedBy
                    ]
                );

                event(new AttendanceRecorded($attendance));
                $results[] = $attendance;
            }

            $this->clearAttendanceCache();
        });

        return $results;
    }

    public function getMonthlyReport(string $month, ?string $class = null): Collection
    {
        $cacheKey = "attendance_report_{$month}_" . ($class ?? 'all');

        return Cache::remember($cacheKey, 3600, function () use ($month, $class) {
            $query = Student::with(['attendances' => function ($query) use ($month) {
                $query->forMonth($month);
            }]);

            if ($class) {
                $query->where('class', $class);
            }

            return $query->get()->map(function ($student) use ($month) {
                $monthlyAttendances = $student->attendances->where('date', '>=', "{$month}-01")
                    ->where('date', '<=', date('Y-m-t', strtotime("{$month}-01")));

                $presentDays = $monthlyAttendances->where('status', 'present')->count();
                $totalDays = $monthlyAttendances->count();
                $attendancePercentage = $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 0;

                return [
                    'student' => $student,
                    'present_days' => $presentDays,
                    'total_days' => $totalDays,
                    'attendance_percentage' => round($attendancePercentage, 2),
                    'absent_days' => $totalDays - $presentDays,
                ];
            });
        });
    }

    public function getTodaySummary(): array
    {
        $cacheKey = 'attendance_today_summary_' . now()->format('Y-m-d');

        return Cache::remember($cacheKey, 300, function () {
            $today = now()->format('Y-m-d');

            $totalStudents = Student::count();
            $presentCount = Attendance::where('date', $today)
                ->where('status', 'present')
                ->count();
            $absentCount = Attendance::where('date', $today)
                ->where('status', 'absent')
                ->count();
            $lateCount = Attendance::where('date', $today)
                ->where('status', 'late')
                ->count();

            $recordedCount = $presentCount + $absentCount + $lateCount;

            return [
                'total_students' => $totalStudents,
                'present_count' => $presentCount,
                'absent_count' => $absentCount,
                'late_count' => $lateCount,
                'recorded_count' => $recordedCount,
                'present_percentage' => $recordedCount > 0 ? round(($presentCount / $recordedCount) * 100, 2) : 0,
                'overall_percentage' => $totalStudents > 0 ? round(($recordedCount / $totalStudents) * 100, 2) : 0,
            ];
        });
    }

    public function getClassSummary(string $class, string $date): array
    {
        $cacheKey = "attendance_class_summary_{$class}_{$date}";

        return Cache::remember($cacheKey, 300, function () use ($class, $date) {
            $totalStudents = Student::where('class', $class)->count();
            $attendances = Attendance::where('date', $date)
                ->whereHas('student', function ($query) use ($class) {
                    $query->where('class', $class);
                })->get();

            $presentCount = $attendances->where('status', 'present')->count();
            $absentCount = $attendances->where('status', 'absent')->count();
            $lateCount = $attendances->where('status', 'late')->count();

            return [
                'class' => $class,
                'total_students' => $totalStudents,
                'present_count' => $presentCount,
                'absent_count' => $absentCount,
                'late_count' => $lateCount,
                'recorded_count' => $presentCount + $absentCount + $lateCount,
                'present_percentage' => $totalStudents > 0 ? round(($presentCount / $totalStudents) * 100, 2) : 0,
            ];
        });
    }

    private function clearAttendanceCache(): void
    {
        $today = now()->format('Y-m-d');
        Cache::forget('attendance_today_summary_' . $today);

        // Clear monthly report caches
        $currentMonth = now()->format('Y-m');
        Cache::forget("attendance_report_{$currentMonth}_all");

        $classes = Student::distinct()->pluck('class');
        foreach ($classes as $class) {
            Cache::forget("attendance_report_{$currentMonth}_{$class}");
            Cache::forget("attendance_class_summary_{$class}_{$today}");
        }
    }
}
