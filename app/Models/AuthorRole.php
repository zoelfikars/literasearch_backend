<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Str;

class AuthorRole extends Model
{
    use HasFactory, HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'name', 'slug'];
    public function scopeSearch($q, string $term)
    {
        $like = '%' . trim($term) . '%';
        return $q->where('name', 'like', $like)
            ->orWhere('slug', 'like', '%' . Str::appSlug($term) . '%')
        ;
    }
    public function authors()
    {
        return $this->belongsToMany(Author::class, 'edition_authors', 'role_id', 'author_id')
            ->withPivot(['edition_id'])
            ->withTimestamps();
    }

    public function editions()
    {
        return $this->belongsToMany(Edition::class, 'edition_authors', 'role_id', 'edition_id')
            ->withPivot(['author_id'])
            ->withTimestamps();
    }
}
