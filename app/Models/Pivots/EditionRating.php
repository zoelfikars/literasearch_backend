<?php

namespace App\Models\Pivots;

use App\Models\Edition;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EditionRating extends Pivot
{
    protected $table = 'edition_ratings';
    public $incrementing = false;
    protected $fillable = ['edition_id', 'user_id', 'rating'];

    protected $casts = [
        'rating' => 'double',
    ];

    public function edition()
    {
        return $this->belongsTo(Edition::class, 'edition_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
