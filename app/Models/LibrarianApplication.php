<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LibrarianApplication extends Model
{
    use HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'library_id',
        'user_id',
        'status_id',
        'reviewed_by',
        'review_notes',
        'reviewed_at',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function library()
    {
        return $this->belongsTo(Library::class);
    }
    public function status()
    {
        return $this->belongsTo(Status::class);
    }
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
