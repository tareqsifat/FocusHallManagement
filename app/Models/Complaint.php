<?php

namespace App\Models;

use App\Enums\ComplaintCategory;
use App\Enums\ComplaintStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Status, tracking_id, assignee and photo are set only by the complaint services, so they are not fillable.
 */
class Complaint extends Model
{
    use HasFactory;

    protected $fillable = ['category', 'title', 'description', 'location_text'];

    protected $attributes = ['status' => 'submitted'];

    protected function casts(): array
    {
        return [
            'category' => ComplaintCategory::class,
            'status' => ComplaintStatus::class,
            'is_anonymous' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'tracking_id';
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ComplaintStatusLog::class)->orderBy('created_at')->orderBy('id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ComplaintComment::class)->orderBy('created_at')->orderBy('id');
    }

    /** Hall admins see everything; others see complaints they filed or that are assigned to them (BR-08). */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isHallAdmin()) {
            return;
        }

        $query->where(fn ($q) => $q
            ->where('assigned_to', $user->id)
            ->orWhere('student_profile_id', $user->studentProfile?->id ?? 0));
    }

    /** BR-10: not finished and older than the overdue threshold. */
    public function scopeOverdue(Builder $query): void
    {
        $query->whereNotIn('status', ComplaintStatus::finished())
            ->where('created_at', '<', now()->subDays(config('hall.overdue_days')));
    }

    public function scopeOpen(Builder $query): void
    {
        $query->whereNotIn('status', ComplaintStatus::finished());
    }

    /** Whole days since submission, counted until now while open or until resolution. */
    public function daysOpen(): int
    {
        return (int) $this->created_at->diffInDays($this->resolved_at ?? now());
    }

    public function isOverdue(): bool
    {
        return ! $this->status->isFinished()
            && $this->created_at->lt(now()->subDays(config('hall.overdue_days')));
    }

    public function canBeReopened(): bool
    {
        return $this->status === ComplaintStatus::Resolved
            && $this->resolved_at?->gte(now()->subDays(config('hall.reopen_days')));
    }

    /**
     * Name to show for someone who acted on this complaint. The student who filed an
     * anonymous complaint appears as "Anonymous student" to viewers without viewReporter.
     */
    public function displayName(User $person, bool $reporterVisible): string
    {
        return ! $reporterVisible && $person->id === $this->student->user_id ? 'Anonymous student' : $person->name;
    }

    /**
     * The room number is left out for viewers who may not see an anonymous complaint's reporter,
     * since with 2–4 students per room it would come close to naming them.
     */
    public function locationLabel(bool $showRoom = true): string
    {
        if (! $this->room) {
            return (string) $this->location_text;
        }

        return $showRoom ? "{$this->room->block->name} · Room {$this->room->room_no}" : "{$this->room->block->name} block";
    }
}
