<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasUuids;
    protected $table = 'role_permissions';
    public $incrementing = false;
    protected $fillable = [
        'role_id',
        'permission_id',
    ];
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
