<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintComment extends Model
{
    protected $fillable = ['body'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
