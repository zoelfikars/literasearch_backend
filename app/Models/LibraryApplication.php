<?php

namespace App\Models;

use Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use URL;

class LibraryApplication extends Model
{
    use HasUuids, SoftDeletes;
    protected $fillable = [
        'library_id',
        'user_id',
        'document_path',
        'expiration_date',
        'status_id',
        'inspector_id',
        'inspected_at',
        'rejection_reason',
        'is_verified',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'inspected_at' => 'datetime',
        'expiration_date' => 'datetime',
    ];
    protected function documentUrlCacheKey(): string
    {
        $root = config('app.url');
        $version = md5(($this->document_path ?? '') . '|' . optional($this->updated_at)->timestamp . '|' . $root);
        return "library_application:{$this->getKey()}:document_url:{$version}";
    }
    public function getDocumentSignedUrlAttribute(): ?string
    {
        if (empty($this->document_path)) {
            return null;
        }
        $ttl = now()->addMinutes(10)->subSeconds(5);
        return Cache::remember($this->documentUrlCacheKey(), $ttl, function () {
            return URL::temporarySignedRoute(
                'api.libraries.applications.document',
                now()->addMinutes(10),
                ['application' => $this->getKey()]
            );
        });
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
    public function library()
    {
        return $this->belongsTo(Library::class, 'library_id')->withTrashed();
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
    public function scopeSearch(Builder $q, ?string $needle)
    {
        if (!$needle)
            return $q;
        $like = '%' . mb_strtolower($needle) . '%';

        return $q
            ->leftJoin('libraries as la_lib', 'la_lib.id', '=', 'library_applications.library_id')
            ->leftJoin('statuses as la_st', 'la_st.id', '=', 'library_applications.status_id')
            ->leftJoin('users as la_ins', 'la_ins.id', '=', 'library_applications.inspector_id')
            ->where(function ($w) use ($like) {
                $w->whereRaw('LOWER(la_lib.name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(la_st.name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(la_st.description) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(la_ins.nickname) LIKE ?', [$like]);
            })
            ->select('library_applications.*')
            ->distinct('library_applications.id');
    }
    public function scopeOwnedBy($q, string $userId)
    {
        return $q->where('library_applications.user_id', $userId);
    }
}
