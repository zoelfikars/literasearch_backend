<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EditionComment extends Model
{
    use HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'library_id',
        'user_id',
        'text',
    ];
    public function library()
    {
        return $this->belongsTo(Library::class, 'library_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
