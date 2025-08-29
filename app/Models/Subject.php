<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Str;

class Subject extends Model
{

    use HasFactory, HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['name', 'slug'];
    public function scopeSearch($q, string $term)
    {
        $like = '%' . trim($term) . '%';
        return $q->where('name', 'like', $like)
            ->orWhere('slug', 'like', '%' . Str::appSlug($term) . '%');
    }
    public function editions()
    {
        return $this->belongsToMany(Edition::class, 'edition_subjects', 'subject_id', 'edition_id');
    }
}
