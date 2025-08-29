<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Str;

class Publisher extends Model
{
    use HasFactory, HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'name',
        'slug_name',
        'city',
        'slug_city',
        'address',
    ];
    public function scopeSearch($q, string $term)
    {
        $like = '%' . trim($term) . '%';
        return $q->where('name', 'like', $like)
            ->orWhere('slug_name', 'like', '%' . Str::appSlug($term) . '%')
            ->orWhere('city', 'like', $like)
            ->orWhere('slug_city', 'like', '%' . Str::appSlug($term) . '%')
        ;
    }
    public function editions()
    {
        return $this->hasMany(Edition::class, 'publisher_id');
    }
}
