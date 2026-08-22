<?php
namespace App\Modules\PeopleHr\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use HasUuids;

    protected $fillable = [
        'full_name', 'phone', 'email', 'base_salary', 'salary_type',
        'performance_score', 'status', 'branch_id', 'joined_date',
        'specialization', 'qualification', 'contract_type', 'user_id',
    ];

    protected $casts = ['base_salary' => 'decimal:2', 'performance_score' => 'decimal:2', 'joined_date' => 'date'];

    public function evaluations(): HasMany { return $this->hasMany(TeacherEvaluation::class); }

    public function salaryTypeLabel(): string
    {
        return match($this->salary_type) {
            'fixed' => 'Fixed Salary',
            'per_skill' => 'Per Skill',
            'per_session' => 'Per Session',
            'hybrid' => 'Hybrid (Base + Skills)',
            'per_level' => 'Per Level',
            default => $this->salary_type,
        };
    }
}
