<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LibrarianApplication extends Model
{
    use HasUuids;
    protected $fillable = [
        'user_id',
        'library_id',
        'institution_document_path',
        'status_id',
        'verified_by',
        'verified_at',
        'rejected_reason',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
    public function status()
    {
        return $this->belongsTo(Status::class);
    }
    public function library()
    {
        return $this->belongsTo(Library::class);
    }
}
