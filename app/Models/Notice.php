<?php

namespace App\Models;

use App\Enums\NoticeCategory;
use App\Enums\NoticeStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notice extends Model
{
    use HasFactory;

    // attachment_path, published_at and created_by are set by NoticeService only.
    protected $fillable = ['title', 'body', 'category', 'status', 'is_pinned'];

    protected $attributes = ['status' => 'draft', 'is_pinned' => false];

    protected function casts(): array
    {
        return [
            'category' => NoticeCategory::class,
            'status' => NoticeStatus::class,
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** BR-11: the only query public pages use. */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', NoticeStatus::Published);
    }

    public function scopeBoardOrder(Builder $query): void
    {
        $query->orderByDesc('is_pinned')->orderByDesc('published_at')->orderByDesc('id');
    }

    public function isPublished(): bool
    {
        return $this->status === NoticeStatus::Published;
    }
}
