<?php

namespace App\Services;

use App\Enums\MembershipStatus;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClubMembershipService
{
    /** BR-12: one pending or active request per student per club, backed by a unique index. */
    public function request(Club $club, StudentProfile $student): ClubMembership
    {
        $open = $club->memberships()
            ->where('student_profile_id', $student->id)
            ->whereIn('status', [MembershipStatus::Pending, MembershipStatus::Active])
            ->first();

        if ($open) {
            $this->fail($open->status === MembershipStatus::Active
                ? 'You are already a member of this club.'
                : 'You already have a pending request for this club.');
        }

        try {
            $membership = new ClubMembership;
            $membership->club_id = $club->id;
            $membership->student_profile_id = $student->id;
            $membership->status = MembershipStatus::Pending;
            $membership->save();

            return $membership;
        } catch (UniqueConstraintViolationException) {
            $this->fail('You already have a pending request for this club.');
        }
    }

    public function approve(ClubMembership $membership, User $by): void
    {
        $this->decide($membership, MembershipStatus::Pending, MembershipStatus::Active, $by);
    }

    public function reject(ClubMembership $membership, User $by): void
    {
        $this->decide($membership, MembershipStatus::Pending, MembershipStatus::Rejected, $by);
    }

    public function remove(ClubMembership $membership, User $by): void
    {
        $this->decide($membership, MembershipStatus::Active, MembershipStatus::Removed, $by);
    }

    private function decide(ClubMembership $membership, MembershipStatus $from, MembershipStatus $to, User $by): void
    {
        DB::transaction(function () use ($membership, $from, $to, $by) {
            $membership = ClubMembership::lockForUpdate()->findOrFail($membership->id);

            if ($membership->status !== $from) {
                $this->fail("This membership is {$membership->status->value}, not {$from->value}.");
            }

            $membership->status = $to;
            $membership->decided_by = $by->id;
            $membership->decided_at = now();
            $membership->save();
        });
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['membership' => $message]);
    }
}
