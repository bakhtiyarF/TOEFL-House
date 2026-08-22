<?php
namespace App\Modules\FundingImpact\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasUuids;

    protected $fillable = [
        'campaign_id', 'donor_id', 'amount', 'date', 'restricted',
        'restriction_note', 'receipt_no', 'branch_id',
    ];

    protected $casts = ['amount' => 'decimal:2', 'date' => 'date', 'restricted' => 'boolean'];
}
