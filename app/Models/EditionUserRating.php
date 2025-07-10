<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditionUserRating extends Model
{
    use HasFactory;
    protected $fillable = [
        'edition_id',
        'user_id',
        'rating',
        'text'
    ];
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
