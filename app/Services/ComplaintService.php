<?php

namespace App\Services;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ComplaintService
{
    public function __construct(private TrackingIdGenerator $trackingIds) {}

    /**
     * Files a complaint for the student. A resident's room is filled in automatically;
     * an attached student supplies location_text instead.
     */
    public function submit(User $student, array $data, ?UploadedFile $photo): Complaint
    {
        $profile = $student->studentProfile()->with('currentAllocation.seat')->firstOrFail();
        $roomId = $profile->currentAllocation?->seat->room_id;

        // Stored under a random name on the private disk (BR-13); served only through ComplaintPhotoController.
        $photoPath = $photo?->store('complaints', 'local');
        $trackingId = $this->trackingIds->next();

        try {
            return DB::transaction(function () use ($student, $profile, $roomId, $data, $photoPath, $trackingId) {
                $complaint = new Complaint([
                    'category' => $data['category'],
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'location_text' => $roomId ? null : $data['location_text'],
                ]);
                $complaint->tracking_id = $trackingId;
                $complaint->student_profile_id = $profile->id;
                $complaint->room_id = $roomId;
                $complaint->photo_path = $photoPath;
                $complaint->status = ComplaintStatus::Submitted;
                $complaint->save();

                $complaint->statusLogs()->create([
                    'from_status' => null,
                    'to_status' => ComplaintStatus::Submitted,
                    'changed_by' => $student->id,
                ]);

                return $complaint;
            });
        } catch (Throwable $e) {
            if ($photoPath) {
                Storage::disk('local')->delete($photoPath);
            }
            throw $e;
        }
    }

    public function assign(Complaint $complaint, User $assignee): void
    {
        $complaint->assigned_to = $assignee->id;
        $complaint->save();
    }

    public function comment(Complaint $complaint, User $author, string $body): void
    {
        $comment = $complaint->comments()->make(['body' => $body]);
        $comment->user_id = $author->id;
        $comment->save();
    }
}
