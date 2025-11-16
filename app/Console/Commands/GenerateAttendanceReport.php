<?php
// app/Console/Commands/GenerateAttendanceReport.php

namespace App\Console\Commands;

use App\Services\AttendanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateAttendanceReport extends Command
{
    protected $signature = 'attendance:generate-report
                            {month : The month in Y-m format (e.g., 2024-01)}
                            {class? : The class to filter by}';

    protected $description = 'Generate monthly attendance report in CSV format';

    public function handle(AttendanceService $attendanceService): int
    {
        $month = $this->argument('month');
        $class = $this->argument('class');

        // Validate month format
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $this->error('Invalid month format. Please use Y-m format (e.g., 2024-01).');
            return Command::FAILURE;
        }

        $this->info("Generating attendance report for month: {$month}" . ($class ? " class: {$class}" : ''));

        try {
            $report = $attendanceService->getMonthlyReport($month, $class);

            if ($report->isEmpty()) {
                $this->warn('No attendance data found for the specified criteria.');
                return Command::SUCCESS;
            }

            $filename = "attendance_report_{$month}" . ($class ? "_{$class}" : '') . "_" . now()->format('Y-m-d_H-i-s') . ".csv";

            $csvContent = "Student ID,Name,Class,Section,Present Days,Absent Days,Total Days,Attendance %\n";

            foreach ($report as $record) {
                $csvContent .= sprintf(
                    "%s,%s,%s,%s,%d,%d,%d,%.2f%%\n",
                    $record['student']->student_id,
                    $record['student']->name,
                    $record['student']->class,
                    $record['student']->section,
                    $record['present_days'],
                    $record['absent_days'],
                    $record['total_days'],
                    $record['attendance_percentage']
                );
            }

            Storage::put("reports/{$filename}", $csvContent);

            $filePath = storage_path("app/reports/{$filename}");
            $this->info("Report generated successfully: {$filePath}");
            $this->info("Total records: " . $report->count());
            $this->info("File size: " . number_format(strlen($csvContent)) . " bytes");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error generating report: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
