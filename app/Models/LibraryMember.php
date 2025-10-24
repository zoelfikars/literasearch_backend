<?php
namespace App\Models;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class LibraryMember extends Model
{
    use SoftDeletes;
    protected $primaryKey = null;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'library_id',
        'user_id',
        'is_active'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function library()
    {
        return $this->belongsTo(Library::class, 'library_id');
    }
    public function approvedApplication()
    {
        return $this->hasOne(MembershipApplication::class, 'user_id', 'user_id')
            ->whereColumn('membership_applications.library_id', 'library_id')
            ->approved()
            ->latest('inspected_at');
    }
    public function scopeForLibrary($q, string $libraryId)
    {
        return $q->where('library_id', $libraryId);
    }
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
    public function scopeInactive($q)
    {
        return $q->where('is_active', false);
    }
    public function scopeSearch($q, ?string $needle)
    {
        if (!$needle)
            return $q;
        $like = '%' . $needle . '%';
        return $q
            ->whereHas('user', function ($q) use ($like) {
                $q->where('nickname', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereHas('identity', function ($iq) use ($like) {
                        $iq->where('full_name', 'like', $like);
                    });
            });
    }
    public function scopeOrderByAllowed($q, string $sort, string $order = 'desc')
    {
        switch ($sort) {
            case 'name':
                $q->leftJoin('users', 'library_members.user_id', '=', 'users.id');
                $q->leftJoin('user_identities', 'users.id', '=', 'user_identities.user_id');
                $q->select('library_members.*');
                return $q->orderBy(
                    DB::raw('COALESCE(user_identities.full_name, users.nickname)'),
                    $order
                );
            default:
                return $q->orderBy('created_at', $order);
        }
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

}
