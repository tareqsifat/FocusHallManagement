<?php

namespace App\Enums;

enum ComplaintStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::InProgress => 'In Progress',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * BR-06 state machine. Resolved → In Progress is the student's reopen.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Submitted => [self::UnderReview, self::Rejected],
            self::UnderReview => [self::InProgress, self::Rejected],
            self::InProgress => [self::Resolved],
            self::Resolved => [self::Closed, self::InProgress],
            self::Closed, self::Rejected => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /** Statuses that count as finished for overdue purposes (BR-10). */
    public static function finished(): array
    {
        return [self::Resolved, self::Closed, self::Rejected];
    }

    public function isFinished(): bool
    {
        return in_array($this, self::finished(), true);
    }

    /** Closed and Rejected are final: no further status change, assignment or comment. */
    public function isLocked(): bool
    {
        return $this->allowedTransitions() === [];
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Submitted => 'bg-blue-100 text-blue-800',
            self::UnderReview => 'bg-indigo-100 text-indigo-800',
            self::InProgress => 'bg-amber-100 text-amber-800',
            self::Resolved => 'bg-green-100 text-green-800',
            self::Closed => 'bg-gray-200 text-gray-700',
            self::Rejected => 'bg-red-100 text-red-800',
        };
    }
}
