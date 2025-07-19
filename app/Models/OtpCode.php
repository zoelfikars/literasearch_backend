<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    use HasUuids;
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id', 'user_id', 'otp', 'expires_at', 'purpose'];
    protected $dates = ['expires_at',  'verified_at'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
