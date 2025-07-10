<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EditionSubject extends Model
{
    use HasUuids;
    protected $table = 'editions_subjects';
    public $incrementing = false;
    protected $fillable = [
        'edition_id',
        'subject_id',
    ];
    public function edition()
    {
        return $this->belongsTo(Edition::class);
    }
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
