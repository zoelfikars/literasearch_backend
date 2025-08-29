<?php

namespace App\Models\Pivots;

use App\Models\Edition;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EditionWishlist extends Pivot
{
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
