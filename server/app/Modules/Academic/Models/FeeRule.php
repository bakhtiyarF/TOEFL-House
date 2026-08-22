<?php
namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FeeRule extends Model
{
    use HasUuids;

    protected $fillable = [
        'program_version_id', 'level_id', 'branch_id', 'fee_type',
        'amount', 'currency', 'is_optional', 'effective_from', 'effective_to', 'version',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_optional' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];
}
