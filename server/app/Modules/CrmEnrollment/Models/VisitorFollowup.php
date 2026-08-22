<?php
namespace App\Modules\CrmEnrollment\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VisitorFollowup extends Model
{
    use HasUuids;

    protected $fillable = ['visitor_id', 'date', 'notes', 'operator', 'outcome'];

    protected $casts = ['date' => 'date'];
}
