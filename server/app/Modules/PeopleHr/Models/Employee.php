<?php
namespace App\Modules\PeopleHr\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasUuids;

    protected $fillable = [
        'full_name', 'phone', 'email', 'role', 'base_salary',
        'status', 'branch_id', 'joined_date', 'user_id',
    ];

    protected $casts = ['base_salary' => 'decimal:2', 'joined_date' => 'date'];
}
