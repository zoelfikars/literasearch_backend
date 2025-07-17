<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = [
        'name',
        'description',
    ];
    protected $keyType = 'string';
    public $incrementing = false;
    public function users()
    {
        return $this->hasMany(User::class, 'account_status');
    }
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }
    public function librarianApplication()
    {
        return $this->hasMany(LibrarianApplication::class);
    }
    public function membershipApplication()
    {
        return $this->hasMany(MembershipApplication::class);
    }
}
