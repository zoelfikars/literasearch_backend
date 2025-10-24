<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Builder;
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
        'inspector_id',
        'inspected_at',
        'rejection_reason',
    ];
    protected $casts = [
        'inspected_at' => 'datetime',
        'created_at' => 'datetime',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function library()
    {
        return $this->belongsTo(Library::class, 'library_id')->withTrashed();
    }
    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
    public function scopeWithStatusName($q, string $name)
    {
        return $q->whereHas('status', function ($qq) use ($name) {
            $qq->where('type', 'membership_application')
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
    public function scopeStatusName($q, ?string $name)
    {
        if (!$name)
            return $q;
        return $q->withStatusName($name);
    }
    public function scopeForLibrary($q, string $libraryId)
    {
        return $q->where('library_id', $libraryId);
    }
    public function scopeSearchUser($q, ?string $needle)
    {
        if (!$needle)
            return $q;
        $like = '%' . $needle . '%';
        return $q->where(function ($w) use ($like) {
            $w->whereHas('user', function ($uq) use ($like) {
                $uq->where('nickname', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereHas('identity', fn($iq) => $iq->where('full_name', 'like', $like));
            });
        });
    }
    public function scopeOrderByAllowed($q, string $sort, string $order = 'desc')
    {
        return match ($sort) {
            'name' => $q
                ->leftJoin('users', 'membership_applications.user_id', '=', 'users.id')
                ->leftJoin('user_identities', 'users.id', '=', 'user_identities.user_id')
                ->orderByRaw('COALESCE(user_identities.full_name, users.nickname) ' . $order)
                ->select('membership_applications.*'),
            default => $q->orderBy('created_at', $order),
        };
    }
    public function scopeFilterByStatus(Builder $query, ?string $filterType)
    {
        if (!$filterType) {
            return $query;
        }
        switch (strtolower($filterType)) {
            case 'is_active':
                return $query->where('is_active', true);
            case 'inactive':
                return $query->where('is_active', false);
            default:
                return $query;
        }
    }

    public function scopeOwnedBy($q, string $userId)
    {
        return $q->where('membership_applications.user_id', $userId);
    }
    public function scopeSearch($q, ?string $needle)
    {
        if (!$needle)
            return $q;
        $like = '%' . $needle . '%';
        $q->leftJoin('users as u_search', 'membership_applications.user_id', '=', 'u_search.id');
        $q->leftJoin('user_identities as ui_search', 'u_search.id', '=', 'ui_search.user_id');
        $q->select('membership_applications.*');

        return $q->where(function ($q) use ($like) {
            $q->where('u_search.nickname', 'like', $like)
                ->orWhere('u_search.email', 'like', $like)
                ->orWhereHas('status', fn($sq) => $sq->where('name', 'like', $like)->orWhere('description', 'like', $like));
        });
    }
}
