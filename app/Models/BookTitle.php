<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookTitle extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['title'];
    protected $keyType = 'string';
    public $incrementing = false;
    public function editions(): HasMany
    {
        return $this->hasMany(Edition::class);
    }
}
