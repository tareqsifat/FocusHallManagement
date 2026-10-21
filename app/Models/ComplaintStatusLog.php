<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintStatusLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['from_status', 'to_status', 'note', 'changed_by'];

    protected function casts(): array
    {
        return [
            'from_status' => ComplaintStatus::class,
            'to_status' => ComplaintStatus::class,
        ];
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
