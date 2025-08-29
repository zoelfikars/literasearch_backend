<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LibraryApplication extends Model
{
    use HasUuids, SoftDeletes;
    protected $fillable = [
        'library_id',
        'user_id',
        'document_path',
        'expiration_date',
        'status_id',
        'reviewed_by',
        'reviewed_at',
        'rejected_reason',
        'is_verified',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
    public function library()
    {
        return $this->belongsTo(Library::class, 'library_id');
    }
    public function scopeWithStatusName($q, string $name)
    {
        return $q->whereHas('status', function ($qq) use ($name) {
            $qq->where('type', 'library_application')
                ->where('name', $name);
        });
    }

    public function scopePending($q)
    {
        return $q->withStatusName('pending');
    }

    public function scopeApproved($q)
    {
        return $q->withStatusName('approved');
    }

    public function scopeRejected($q)
    {
        return $q->withStatusName('rejected');
    }

}
