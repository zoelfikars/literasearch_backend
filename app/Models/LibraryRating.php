<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryRating extends Model
{
    protected $fillable = [
        'library_id',
        'user_id',
        'rating',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function library()
    {
        return $this->belongsTo(Library::class);
    }
}
