<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Library extends Model
{
    use HasFactory, HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    public function editions(): BelongsToMany
    {
        return $this->belongsToMany(Edition::class, 'edition_library_stocks')
            ->withPivot('stock_total', 'stock_available');
    }
}
