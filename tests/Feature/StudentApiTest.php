<?php
// tests/Feature/StudentApiTest.php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_can_list_students_with_filters(): void
    {
        Student::factory()->create(['class' => '10A', 'section' => 'A']);
        Student::factory()->create(['class' => '10B', 'section' => 'B']);

        $response = $this->getJson('/api/students?class=10A&section=A');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'student_id', 'name', 'class', 'section']
                    ],
                    'links',
                    'meta'
                ]);
    }

    public function test_can_create_student_with_photo(): void
    {
        Storage::fake('public');

        $studentData = [
            'student_id' => 'STU001',
            'name' => 'John Doe',
            'class' => '10A',
            'section' => 'A',
            'photo' => UploadedFile::fake()->image('student.jpg')
        ];

        $response = $this->postJson('/api/students', $studentData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'data' => ['id', 'student_id', 'name', 'photo_url']
                ]);

        $this->assertDatabaseHas('students', [
            'student_id' => 'STU001',
            'name' => 'John Doe'
        ]);

        Storage::disk('public')->assertExists('students/' . $studentData['photo']->hashName());
    }

    public function test_can_update_student(): void
    {
        $student = Student::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'class' => '11A'
        ];

        $response = $this->putJson("/api/students/{$student->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Student updated successfully',
                    'data' => [
                        'name' => 'Updated Name',
                        'class' => '11A'
                    ]
                ]);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Updated Name',
            'class' => '11A'
        ]);
    }
}
