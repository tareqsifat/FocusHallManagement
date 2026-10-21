<?php

namespace App\Services;

use App\Enums\ComplaintCategory;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Complaint;
use App\Models\Seat;
use App\Models\SeatAllocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DashboardService
{
    /** Office Staff / Provost / Super Admin figures. */
    public function hallFigures(): array
    {
        $totalSeats = Seat::where('is_active', true)->count();
        $occupied = SeatAllocation::whereNull('vacated_at')->count();

        return [
            'total_seats' => $totalSeats,
            'occupied_seats' => $occupied,
            'occupancy_percent' => $totalSeats ? round($occupied / $totalSeats * 100, 1) : 0.0,
            'pending_registrations' => User::where('status', UserStatus::Pending)
                ->whereHas('roles', fn ($q) => $q->where('name', RoleName::Student))
                ->count(),
        ];
    }

    /**
     * Complaint figures, optionally limited to complaints raised from rooms in the given blocks
     * (house tutor view). Complaints without a room (attached students) belong to no block.
     *
     * @param  list<int>|null  $blockIds  null = whole hall
     */
    public function complaintFigures(?array $blockIds = null): array
    {
        $scoped = fn (): Builder => Complaint::query()
            ->when($blockIds !== null, fn ($q) => $q->whereHas('room', fn ($r) => $r->whereIn('block_id', $blockIds)));

        $openByCategory = $scoped()->open()
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        // BR-10: resolution time = submitted → resolved, for complaints resolved in the last 30 days.
        // ponytail: averaged in PHP to stay portable across MySQL/SQLite; fine for a hall's monthly volume.
        $durations = $scoped()
            ->where('resolved_at', '>=', now()->subDays(30))
            ->get(['created_at', 'resolved_at'])
            ->map(fn (Complaint $c) => $c->created_at->diffInSeconds($c->resolved_at) / 86400);

        return [
            // Every category is listed, zeros included, in enum order so the chart never reshuffles.
            'open_by_category' => collect(ComplaintCategory::cases())
                ->mapWithKeys(fn (ComplaintCategory $c) => [$c->label() => (int) ($openByCategory[$c->value] ?? 0)])
                ->all(),
            'open_total' => (int) $openByCategory->sum(),
            'overdue' => $scoped()->overdue()->count(),
            'avg_resolution_days' => $durations->isEmpty() ? null : round($durations->avg(), 1),
            'resolved_last_30_days' => $durations->count(),
        ];
    }
}
