<?php

namespace App\Models\Pivots;

use App\Models\Edition;
use App\Models\Library;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EditionLibrary extends Pivot
{
    protected $fillable = [
        'edition_id',
        'library_id',
        'stock_total',
        'stock_available',
    ];
    public function edition()
    {
        return $this->belongsTo(Edition::class);
    }
    public function library()
    {
        return $this->belongsTo(Library::class);
    }
}
