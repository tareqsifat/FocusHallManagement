<?php

namespace App\Policies;

use App\Enums\ComplaintStatus;
use App\Enums\RoleName;
use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Complaint $complaint): bool
    {
        return $user->isHallAdmin() || $this->isAssignee($user, $complaint) || $this->isOwner($user, $complaint);
    }

    /** BR-03: only approved students (the active middleware already rules out the rest). */
    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::Student) && $user->isActive() && $user->studentProfile !== null;
    }

    public function assign(User $user, Complaint $complaint): bool
    {
        return $user->isHallAdmin();
    }

    public function comment(User $user, Complaint $complaint): bool
    {
        return $this->view($user, $complaint);
    }

    /** Coarse check: may this user attempt any status change at all? */
    public function changeStatus(User $user, Complaint $complaint): bool
    {
        return $user->isHallAdmin() || $this->isAssignedTutor($user, $complaint) || $this->isOwner($user, $complaint);
    }

    /**
     * Who may make a particular move. Validity of the move itself (BR-06) is ComplaintStatusService's job.
     * - Reopening (Resolved → In Progress) belongs to the student who filed it.
     * - Closing and rejecting are hall admin decisions.
     * - Other moves: hall admins, or the house tutor it is assigned to (BR-08).
     */
    public function transition(User $user, Complaint $complaint, ComplaintStatus $to): bool
    {
        if ($complaint->status === ComplaintStatus::Resolved && $to === ComplaintStatus::InProgress) {
            return $this->isOwner($user, $complaint);
        }

        if (in_array($to, [ComplaintStatus::Closed, ComplaintStatus::Rejected], true)) {
            return $user->isHallAdmin();
        }

        return $user->isHallAdmin() || $this->isAssignedTutor($user, $complaint);
    }

    /**
     * Who filed an anonymous complaint is visible only to the Provost and Super Admin
     * (Section 5.2), and of course to the student who filed it.
     */
    public function viewReporter(User $user, Complaint $complaint): bool
    {
        return ! $complaint->is_anonymous
            || $user->hasRole(RoleName::Provost, RoleName::SuperAdmin)
            || $this->isOwner($user, $complaint);
    }

    private function isOwner(User $user, Complaint $complaint): bool
    {
        return $user->studentProfile !== null && $complaint->student_profile_id === $user->studentProfile->id;
    }

    private function isAssignee(User $user, Complaint $complaint): bool
    {
        return $complaint->assigned_to === $user->id;
    }

    private function isAssignedTutor(User $user, Complaint $complaint): bool
    {
        return $this->isAssignee($user, $complaint) && $user->hasRole(RoleName::HouseTutor);
    }
}
