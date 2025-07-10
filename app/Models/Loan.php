<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = [
        'user_id',
        'edition_id',
        'library_id',
        'status_id',
        'loaned_at',
        'due_date',
        'returned_at',
        'notes',
        'approved_by',
        'rejected_reason'
    ];
    protected $keyType = 'string';
    public $incrementing = false;
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }
    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }
}
