<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LibraryLibrarian extends Model
{
    protected $primaryKey = ['library_id', 'user_id'];
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'library_id',
        'user_id',
        'is_active',
    ];
    protected $casts = [
        'library_id' => 'string',
        'user_id' => 'string',
        'is_active' => 'boolean',
    ];
}
