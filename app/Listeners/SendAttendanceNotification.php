<?php
// app/Listeners/SendAttendanceNotification.php

namespace App\Listeners;

use App\Events\AttendanceRecorded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendAttendanceNotification implements ShouldQueue
{
    public function handle(AttendanceRecorded $event): void
    {
        $attendance = $event->attendance;

        Log::info('Attendance recorded', [
            'student_id' => $attendance->student_id,
            'student_name' => $attendance->student->name,
            'date' => $attendance->date->format('Y-m-d'),
            'status' => $attendance->status,
            'recorded_by' => $attendance->recordedBy->name,
        ]);

        // In production, you might send:
        // - Email notifications to parents
        // - SMS alerts
        // - Push notifications
    }
}
