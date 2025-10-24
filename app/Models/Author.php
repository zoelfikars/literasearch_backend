<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Str;

class Author extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'name',
        'slug',
        'disambiguator',
    ];
    public function scopeSearch($q, string $term)
    {
        $like = '%' . trim($term) . '%';
        return $q->where('name', 'like', $like)
            ->orWhere('slug', 'like', '%' . Str::appSlug($term) . '%')
            ->orWhere('disambiguator', 'like', $like)
        ;
    }
    public function editions()
    {
        return $this->belongsToMany(Edition::class, 'edition_authors', 'author_id', 'edition_id')
            ->withPivot(['role_id'])
            ->withTimestamps();
    }

    public function roles()
    {
        return $this->belongsToMany(AuthorRole::class, 'edition_authors', 'author_id', 'role_id')
            ->withPivot(['edition_id'])
            ->withTimestamps();
    }
}
