<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Str;

class Language extends Model
{
    use HasFactory, HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'english_name',
        'native_name',
        'iso_639_1',
        'iso_639_3',
        'direction',
    ];
    public function scopeSearch($q, string $term)
    {
        $like = '%' . trim($term) . '%';
        return $q->where('english_name', 'like', $like)
            ->orWhere('native_name', 'like', $like)
            ->orWhere('iso_639_1', 'like', $like)
            ->orWhere('iso_639_3', 'like', $like)
        ;
    }
}
