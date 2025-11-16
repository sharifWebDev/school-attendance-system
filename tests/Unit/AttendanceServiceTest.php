<?php
// tests/Unit/AttendanceServiceTest.php

namespace Tests\Unit;

use App\Events\AttendanceRecorded;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceService $attendanceService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attendanceService = app(AttendanceService::class);
        $this->user = User::factory()->create();
    }

    public function test_record_bulk_attendance_successfully(): void
    {
        Event::fake();

        $student1 = Student::factory()->create();
        $student2 = Student::factory()->create();

        $attendanceData = [
            [
                'student_id' => $student1->id,
                'date' => now()->format('Y-m-d'),
                'status' => 'present',
                'note' => 'On time'
            ],
            [
                'student_id' => $student2->id,
                'date' => now()->format('Y-m-d'),
                'status' => 'absent',
                'note' => 'Sick'
            ]
        ];

        $results = $this->attendanceService->recordBulkAttendance($attendanceData, $this->user->id);

        $this->assertCount(2, $results);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student1->id,
            'status' => 'present',
            'note' => 'On time'
        ]);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student2->id,
            'status' => 'absent',
            'note' => 'Sick'
        ]);

        Event::assertDispatchedTimes(AttendanceRecorded::class, 2);
    }

    public function test_get_today_summary_calculates_correctly(): void
    {
        $student1 = Student::factory()->create();
        $student2 = Student::factory()->create();
        $student3 = Student::factory()->create();

        Attendance::factory()->create([
            'student_id' => $student1->id,
            'date' => now()->format('Y-m-d'),
            'status' => 'present'
        ]);

        Attendance::factory()->create([
            'student_id' => $student2->id,
            'date' => now()->format('Y-m-d'),
            'status' => 'absent'
        ]);

        $summary = $this->attendanceService->getTodaySummary();

        $this->assertEquals(3, $summary['total_students']);
        $this->assertEquals(1, $summary['present_count']);
        $this->assertEquals(1, $summary['absent_count']);
        $this->assertEquals(0, $summary['late_count']);
        $this->assertEquals(2, $summary['recorded_count']);
        $this->assertEquals(50.0, $summary['present_percentage']);
        $this->assertEquals(66.67, $summary['overall_percentage']);
    }

    public function test_monthly_report_generation_with_caching(): void
    {
        $student = Student::factory()->create(['class' => '10A']);
        $month = now()->format('Y-m');

        Attendance::factory()->create([
            'student_id' => $student->id,
            'date' => "{$month}-01",
            'status' => 'present'
        ]);

        Attendance::factory()->create([
            'student_id' => $student->id,
            'date' => "{$month}-02",
            'status' => 'absent'
        ]);

        $report = $this->attendanceService->getMonthlyReport($month, '10A');

        $this->assertCount(1, $report);
        $this->assertEquals(1, $report->first()['present_days']);
        $this->assertEquals(1, $report->first()['absent_days']);
        $this->assertEquals(2, $report->first()['total_days']);
        $this->assertEquals(50.0, $report->first()['attendance_percentage']);

        // Test caching
        $cacheKey = "attendance_report_{$month}_10A";
        $this->assertTrue(Cache::has($cacheKey));
    }
}
