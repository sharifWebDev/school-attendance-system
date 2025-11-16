<?php
// app/Http/Resources/StudentResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'name' => $this->name,
            'class' => $this->class,
            'section' => $this->section,
            'photo_url' => $this->photo ? asset('storage/' . $this->photo) : null,
            'photo_path' => $this->photo,
            'today_attendance' => $this->whenLoaded('attendances', function () {
                return $this->getTodayAttendance()?->status;
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
