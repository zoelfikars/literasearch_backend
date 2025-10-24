<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class EditionReadPosition extends Model
{
    use HasUuids;
    protected $table = 'edition_read_positions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id',
        'user_id',
        'edition_id',
        'locator_type',
        'page',
        'cfi',
        'progress_percent',
        'last_opened_at',
    ];
    protected $casts = [
        'progress_percent' => 'decimal:2',
        'last_opened_at' => 'datetime',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function edition()
    {
        return $this->belongsTo(Edition::class);
    }
}
