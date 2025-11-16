<?php
// app/Http/Controllers/API/StudentController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Student::query();

        if ($request->has('class')) {
            $query->where('class', $request->class);
        }

        if ($request->has('section')) {
            $query->where('section', $request->section);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('student_id', 'like', "%{$search}%")
                  ->orWhere('class', 'like', "%{$search}%")
                  ->orWhere('section', 'like', "%{$search}%");
            });
        }

        $perPage = min($request->get('per_page', 15), 50); // Max 50 per page
        $students = $query->orderBy('class')->orderBy('section')->orderBy('name')->paginate($perPage);

        return StudentResource::collection($students);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|string|unique:students,student_id|max:20',
            'name' => 'required|string|max:255',
            'class' => 'required|string|max:10',
            'section' => 'required|string|max:5',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('students', 'public');
        }

        $student = Student::create($validated);

        return response()->json([
            'message' => 'Student created successfully',
            'data' => new StudentResource($student)
        ], 201);
    }

    public function show(Student $student): StudentResource
    {
        $student->load('attendances');
        return new StudentResource($student);
    }

    public function update(Request $request, Student $student): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'sometimes|required|string|unique:students,student_id,' . $student->id . '|max:20',
            'name' => 'sometimes|required|string|max:255',
            'class' => 'sometimes|required|string|max:10',
            'section' => 'sometimes|required|string|max:5',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                Storage::disk('public')->delete($student->photo);
            }
            $validated['photo'] = $request->file('photo')->store('students', 'public');
        }

        $student->update($validated);

        return response()->json([
            'message' => 'Student updated successfully',
            'data' => new StudentResource($student)
        ]);
    }

    public function destroy(Student $student): JsonResponse
    {
        // Delete student photo if exists
        if ($student->photo && Storage::disk('public')->exists($student->photo)) {
            Storage::disk('public')->delete($student->photo);
        }

        $student->delete();

        return response()->json([
            'message' => 'Student deleted successfully'
        ]);
    }

    public function getClasses(): JsonResponse
    {
        $classes = Student::distinct()->orderBy('class')->pluck('class');

        return response()->json([
            'data' => $classes
        ]);
    }

    public function getSections(): JsonResponse
    {
        $sections = Student::distinct()->orderBy('section')->pluck('section');

        return response()->json([
            'data' => $sections
        ]);
    }
}
