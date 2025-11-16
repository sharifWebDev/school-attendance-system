<?php
// app/Events/AttendanceRecorded.php

namespace App\Events;

use App\Models\Attendance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceRecorded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The attendance instance.
     *
     * @var \App\Models\Attendance
     */
    public $attendance;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Attendance $attendance
     * @return void
     */
    public function __construct(public Attendance $attendance)
    {
        $this->attendance = $attendance;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('attendance.' . $this->attendance->student_id);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'attendance.recorded';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'id' => $this->attendance->id,
            'student_id' => $this->attendance->student_id,
            'student_name' => $this->attendance->student->name,
            'date' => $this->attendance->date->format('Y-m-d'),
            'status' => $this->attendance->status,
            'note' => $this->attendance->note,
            'recorded_by' => $this->attendance->recordedBy->name,
            'recorded_at' => $this->attendance->created_at->toISOString(),
        ];
    }

    /**
     * Determine if this event should broadcast.
     *
     * @return bool
     */
    public function broadcastWhen()
    {
        return config('broadcasting.default') !== 'log';
    }
}
