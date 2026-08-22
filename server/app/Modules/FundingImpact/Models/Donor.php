<?php
namespace App\Modules\FundingImpact\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Donor extends Model
{
    use HasUuids;

    protected $fillable = ['full_name', 'type', 'phone', 'email', 'country', 'notes'];

    public function donations(): HasMany { return $this->hasMany(Donation::class); }
}
