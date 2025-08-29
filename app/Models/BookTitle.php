<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Str;

class BookTitle extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $fillable = ['title', 'slug'];
    protected $keyType = 'string';
    public $incrementing = false;
    public function editions()
    {
        return $this->hasMany(Edition::class, 'book_title_id', 'id');
    }
    public function scopeSearch($q, string $term)
    {
        $like = '%' . trim($term) . '%';
        return $q->where('title', 'like', $like)
            ->orWhere('slug', 'like', '%' . Str::appSlug($term) . '%');
    }

}
