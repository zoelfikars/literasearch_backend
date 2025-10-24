<?php
namespace App\Models;
use App\Models\Edition;
use App\Models\Library;
use Illuminate\Database\Eloquent\Model;
class LibraryEdition extends Model
{
    protected $table = 'library_editions';
    public $timestamps = true;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = null;
    protected $fillable = [
        'edition_id',
        'library_id',
        'stock_total',
    ];
    protected $casts = [
        'stock_total' => 'integer',
    ];
    public function edition()
    {
        return $this->belongsTo(Edition::class, 'edition_id');
    }
    public function library()
    {
        return $this->belongsTo(Library::class, 'library_id');
    }
}
