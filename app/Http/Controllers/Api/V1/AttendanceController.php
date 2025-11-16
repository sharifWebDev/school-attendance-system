<?php
// app/Http/Controllers/API/AttendanceController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService)
    {
    }

    public function recordBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'attendances' => 'required|array|min:1',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.date' => 'required|date|before_or_equal:today',
            'attendances.*.status' => 'required|in:present,absent,late',
            'attendances.*.note' => 'nullable|string|max:500'
        ]);

        $recordedBy = auth()->id();

        try {
            $results = $this->attendanceService->recordBulkAttendance($validated['attendances'], $recordedBy);

            return response()->json([
                'message' => 'Attendance recorded successfully for ' . count($results) . ' students',
                'data' => AttendanceResource::collection($results)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to record attendance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMonthlyReport(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'class' => 'nullable|string'
        ]);

        try {
            $report = $this->attendanceService->getMonthlyReport(
                $request->month,
                $request->class
            );

            return response()->json([
                'data' => $report,
                'meta' => [
                    'month' => $request->month,
                    'class' => $request->class,
                    'total_records' => $report->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTodaySummary(): JsonResponse
    {
        try {
            $summary = $this->attendanceService->getTodaySummary();

            return response()->json([
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch today\'s summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getClassSummary(Request $request): JsonResponse
    {
        $request->validate([
            'class' => 'required|string',
            'date' => 'required|date'
        ]);

        try {
            $summary = $this->attendanceService->getClassSummary(
                $request->class,
                $request->date
            );

            return response()->json([
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch class summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Attendance::with(['student', 'recordedBy']);

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('date')) {
            $query->where('date', $request->date);
        }

        if ($request->has('class')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('class', $request->class);
            });
        }

        if ($request->has('section')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('section', $request->section);
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min($request->get('per_page', 15), 50);
        $attendances = $query->latest()->paginate($perPage);

        return AttendanceResource::collection($attendances);
    }

    public function show(Attendance $attendance): AttendanceResource
    {
        $attendance->load(['student', 'recordedBy']);
        return new AttendanceResource($attendance);
    }

    public function destroy(Attendance $attendance): JsonResponse
    {
        $attendance->delete();

        return response()->json([
            'message' => 'Attendance record deleted successfully'
        ]);
    }
}
