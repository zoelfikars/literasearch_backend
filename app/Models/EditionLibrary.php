<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EditionLibrary extends Model
{
    use HasUuids;
    public $incrementing = false;
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
