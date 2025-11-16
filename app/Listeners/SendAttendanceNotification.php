<?php
// app/Listeners/SendAttendanceNotification.php

namespace App\Listeners;

use App\Events\AttendanceRecorded;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class SendAttendanceNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'notifications';

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Handle the event.
     *
     * @param \App\Events\AttendanceRecorded $event
     * @return void
     */
    public function handle(AttendanceRecorded $event): void
    {
        $attendance = $event->attendance;
        $student = $attendance->student;
        $recordedBy = $attendance->recordedBy;

        try {
            // Log the attendance record
            $this->logAttendance($attendance, $student, $recordedBy);

            // Send email notification to parents/guardians
            $this->sendEmailNotification($attendance, $student);

            // Send SMS notification (if configured)
            $this->sendSmsNotification($attendance, $student);

            // Send in-app notification to teachers/admins
            $this->sendInAppNotification($attendance, $student, $recordedBy);

            // Update attendance statistics cache
            $this->updateAttendanceStats($attendance);

        } catch (\Exception $e) {
            Log::error('Failed to process attendance notification: ' . $e->getMessage(), [
                'attendance_id' => $attendance->id,
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log attendance record for auditing.
     *
     * @param \App\Models\Attendance $attendance
     * @param \App\Models\Student $student
     * @param \App\Models\User $recordedBy
     * @return void
     */
    private function logAttendance($attendance, $student, $recordedBy): void
    {
        Log::info('Attendance recorded successfully', [
            'attendance_id' => $attendance->id,
            'student_id' => $student->id,
            'student_name' => $student->name,
            'student_class' => $student->class,
            'student_section' => $student->section,
            'date' => $attendance->date->format('Y-m-d'),
            'status' => $attendance->status,
            'note' => $attendance->note,
            'recorded_by_id' => $recordedBy->id,
            'recorded_by_name' => $recordedBy->name,
            'recorded_at' => $attendance->created_at->toISOString(),
            'ip_address' => request()->ip() ?? 'unknown'
        ]);

        // Also log to audit log if configured
        if (config('logging.channels.audit')) {
            Log::channel('audit')->info('ATTENDANCE_RECORDED', [
                'user_id' => $recordedBy->id,
                'action' => 'record_attendance',
                'model' => 'Attendance',
                'model_id' => $attendance->id,
                'details' => [
                    'student_id' => $student->id,
                    'date' => $attendance->date->format('Y-m-d'),
                    'status' => $attendance->status,
                    'previous_status' => $attendance->getOriginal('status') // if updating
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        }
    }

    /**
     * Send email notification to parents/guardians.
     *
     * @param \App\Models\Attendance $attendance
     * @param \App\Models\Student $student
     * @return void
     */
    private function sendEmailNotification($attendance, $student): void
    {
        // In production, you would:
        // 1. Get parent/guardian email from student profile
        // 2. Send formatted email with attendance details

        $emailData = [
            'student_name' => $student->name,
            'student_id' => $student->student_id,
            'class' => $student->class,
            'section' => $student->section,
            'date' => $attendance->date->format('F j, Y'),
            'status' => ucfirst($attendance->status),
            'note' => $attendance->note,
            'school_name' => config('app.name', 'School Attendance System'),
            'current_year' => date('Y')
        ];

        // Example email sending (uncomment when email service is configured)
        /*
        $parentEmail = 'parent@example.com'; // Get from student profile

        Mail::send('emails.attendance-notification', $emailData, function ($message) use ($parentEmail, $student) {
            $message->to($parentEmail)
                   ->subject('Attendance Update for ' . $student->name);
        });
        */

        Log::info('Email notification prepared for attendance', [
            'attendance_id' => $attendance->id,
            'student_id' => $student->id,
            'status' => $attendance->status,
            'email_data' => $emailData
        ]);
    }

    /**
     * Send SMS notification.
     *
     * @param \App\Models\Attendance $attendance
     * @param \App\Models\Student $student
     * @return void
     */
    private function sendSmsNotification($attendance, $student): void
    {
        // In production, integrate with SMS service like Twilio, Nexmo, etc.

        $smsMessage = $this->formatSmsMessage($attendance, $student);
        $phoneNumber = '+1234567890'; // Get from student profile

        // Example SMS sending (uncomment when SMS service is configured)
        /*
        try {
            $twilio = new \Twilio\Rest\Client(
                config('services.twilio.sid'),
                config('services.twilio.token')
            );

            $twilio->messages->create(
                $phoneNumber,
                [
                    'from' => config('services.twilio.from'),
                    'body' => $smsMessage
                ]
            );
        } catch (\Exception $e) {
            Log::error('SMS sending failed: ' . $e->getMessage());
        }
        */

        Log::info('SMS notification prepared for attendance', [
            'attendance_id' => $attendance->id,
            'student_id' => $student->id,
            'status' => $attendance->status,
            'sms_message' => $smsMessage,
            'phone_number' => $phoneNumber
        ]);
    }

    /**
     * Format SMS message for attendance notification.
     *
     * @param \App\Models\Attendance $attendance
     * @param \App\Models\Student $student
     * @return string
     */
    private function formatSmsMessage($attendance, $student): string
    {
        $statusEmoji = [
            'present' => '✅',
            'absent' => '❌',
            'late' => '⏰'
        ];

        $emoji = $statusEmoji[$attendance->status] ?? '📝';

        return sprintf(
            "%s %s is %s today (%s). %s",
            $emoji,
            $student->name,
            strtoupper($attendance->status),
            $attendance->date->format('M j'),
            $attendance->note ? "Note: {$attendance->note}" : ""
        );
    }

    /**
     * Send in-app notification to relevant users.
     *
     * @param \App\Models\Attendance $attendance
     * @param \App\Models\Student $student
     * @param \App\Models\User $recordedBy
     * @return void
     */
    private function sendInAppNotification($attendance, $student, $recordedBy): void
    {
        // Notify class teacher, principal, or other relevant staff
        $notificationData = [
            'attendance_id' => $attendance->id,
            'student_id' => $student->id,
            'student_name' => $student->name,
            'class' => $student->class,
            'section' => $student->section,
            'date' => $attendance->date->format('Y-m-d'),
            'status' => $attendance->status,
            'recorded_by' => $recordedBy->name,
            'timestamp' => now()->toISOString()
        ];

        // Get users to notify (class teacher, principal, etc.)
        $usersToNotify = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['teacher', 'principal', 'admin']);
        })->where('class', $student->class) // If class-specific
          ->orWhere('is_admin', true)
          ->get();

        // Example notification sending (uncomment when notification system is configured)
        /*
        Notification::send($usersToNotify, new AttendanceRecordedNotification($notificationData));
        */

        Log::info('In-app notifications prepared for attendance', [
            'attendance_id' => $attendance->id,
            'student_id' => $student->id,
            'users_to_notify_count' => $usersToNotify->count(),
            'notification_data' => $notificationData
        ]);
    }

    /**
     * Update attendance statistics cache.
     *
     * @param \App\Models\Attendance $attendance
     * @return void
     */
    private function updateAttendanceStats($attendance): void
    {
        $date = $attendance->date->format('Y-m-d');
        $class = $attendance->student->class;

        // Clear relevant cache keys
        $cacheKeys = [
            "attendance_today_summary_{$date}",
            "attendance_class_summary_{$class}_{$date}",
            "attendance_stats_{$date}",
        ];

        foreach ($cacheKeys as $key) {
            \Illuminate\Support\Facades\Cache::forget($key);
        }

        Log::debug('Attendance cache cleared for stats update', [
            'attendance_id' => $attendance->id,
            'date' => $date,
            'class' => $class,
            'cache_keys' => $cacheKeys
        ]);
    }

    /**
     * Handle a job failure.
     *
     * @param \App\Events\AttendanceRecorded $event
     * @param \Throwable $exception
     * @return void
     */
    public function failed(AttendanceRecorded $event, \Throwable $exception): void
    {
        Log::error('Attendance notification job failed', [
            'attendance_id' => $event->attendance->id,
            'student_id' => $event->attendance->student_id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Optionally, send alert to administrators
        $this->sendFailureAlert($event, $exception);
    }

    /**
     * Send failure alert to administrators.
     *
     * @param \App\Events\AttendanceRecorded $event
     * @param \Throwable $exception
     * @return void
     */
    private function sendFailureAlert($event, $exception): void
    {
        $alertData = [
            'event' => 'AttendanceRecorded',
            'attendance_id' => $event->attendance->id,
            'student_id' => $event->attendance->student_id,
            'error_message' => $exception->getMessage(),
            'failed_at' => now()->toISOString(),
            'attempts' => $this->attempts()
        ];

        Log::channel('slack')->error('Attendance Notification Job Failed', $alertData);

        // Or send email to admin

        // Mail::to(config('app.admin_email'))
        //     ->send(new JobFailedNotification($alertData));

    }
}
