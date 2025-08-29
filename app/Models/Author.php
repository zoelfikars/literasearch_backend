<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
    public function editions(): BelongsToMany
    {
        return $this->belongsToMany(Edition::class, 'edition_authors')->withPivot('role', 'subtitle');
    }
}
