<?php

namespace App\Services;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Applies BR-06 / BR-07 transitions. Who may attempt a transition is decided by
 * ComplaintPolicy::transition (403); whether it is valid is decided here (422).
 */
class ComplaintStatusService
{
    public function transition(Complaint $complaint, ComplaintStatus $to, ?string $note, User $by): Complaint
    {
        $note = filled($note) ? trim($note) : null;

        return DB::transaction(function () use ($complaint, $to, $note, $by) {
            $complaint = Complaint::lockForUpdate()->findOrFail($complaint->id);
            $from = $complaint->status;

            if (! $from->canTransitionTo($to)) {
                $this->fail('to_status', "A complaint cannot move from {$from->label()} to {$to->label()}.");
            }
            if ($to === ComplaintStatus::Rejected && ! $note) {
                $this->fail('note', 'A reason is required to reject a complaint.');
            }
            if ($to === ComplaintStatus::Resolved && ! $note) {
                $this->fail('note', 'A resolution note is required to mark a complaint resolved.');
            }
            if ($from === ComplaintStatus::Resolved && $to === ComplaintStatus::InProgress && ! $complaint->canBeReopened()) {
                $this->fail('to_status', 'A resolved complaint can only be reopened within '.config('hall.reopen_days').' days of resolution.');
            }

            $complaint->status = $to;
            if ($to === ComplaintStatus::Resolved) {
                $complaint->resolved_at = now();
            } elseif ($to === ComplaintStatus::InProgress) {
                $complaint->resolved_at = null;
            }
            $complaint->save();

            $complaint->statusLogs()->create([
                'from_status' => $from,
                'to_status' => $to,
                'note' => $note,
                'changed_by' => $by->id,
            ]);

            return $complaint;
        });
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
