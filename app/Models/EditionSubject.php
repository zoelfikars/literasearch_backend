<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EditionSubject extends Model
{
    use HasUuids;
    public $incrementing = false;
    protected $fillable = [
        'edition_id',
        'subject_id',
    ];
    public function editions()
    {
        return $this->belongsTo(Edition::class, 'edition_id');
    }
    public function subjects()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
