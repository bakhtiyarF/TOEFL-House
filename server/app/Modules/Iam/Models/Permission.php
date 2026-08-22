<?php

namespace App\Modules\Iam\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'code',
        'resource',
        'action',
        'description',
        'category',
        'is_system',
        'created_at',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'created_at' => 'datetime',
    ];
}
