<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    use HasUuids;
    protected $fillable = ['user_id', 'token', 'expires_at'];
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;
    protected $casts = [
        'expires_at' => 'datetime',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
