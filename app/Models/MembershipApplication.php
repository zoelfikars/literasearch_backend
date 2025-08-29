<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipApplication extends Model
{
    use HasUuids, SoftDeletes;
    protected $fillable = [
        'user_id',
        'library_id',
        'status_id',
        'verified_by',
        'verified_at',
        'rejected_reason',
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
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
