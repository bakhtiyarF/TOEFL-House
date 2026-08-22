<?php

namespace App\Modules\Iam\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasUuids, HasApiTokens, Notifiable;

    protected $fillable = [
        'username',
        'password',
        'full_name',
        'employee_id',
        'email',
        'phone',
        'address',
        'national_id',
        'emergency_contact',
        'department',
        'employment_type',
        'employee_status',
        'profile_photo_path',
        'account_status',
        'date_of_birth',
        'joining_date',
        'gender',
        'manager_user_id',
        'role',
        'branch_id',
        'linked_teacher_id',
        'linked_employee_id',
        'linked_partner_id',
        'two_factor_enabled',
        'must_change_password',
        'is_active',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'two_factor_enabled' => 'boolean',
        'must_change_password' => 'boolean',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function userRoles()
    {
        return $this->hasMany(UserRole::class);
    }

    public function permissionOverrides()
    {
        return $this->hasMany(PermissionOverride::class);
    }
}
