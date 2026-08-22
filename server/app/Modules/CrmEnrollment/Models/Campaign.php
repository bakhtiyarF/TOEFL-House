<?php
namespace App\Modules\CrmEnrollment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'source', 'start_date', 'end_date', 'budget', 'status', 'branch_id'];

    protected $casts = ['start_date' => 'date', 'end_date' => 'date', 'budget' => 'decimal:2'];
}
