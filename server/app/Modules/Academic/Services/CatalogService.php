<?php

namespace App\Modules\Academic\Services;

use App\Modules\Academic\Models\Program;
use App\Modules\Academic\Models\ProgramVersion;
use App\Modules\Academic\Models\Level;
use App\Modules\Academic\Models\Subject;
use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Catalog Service
 * Manages programs, versions, levels, subjects (curriculum catalog)
 * Implements copy-on-write versioning per spec.
 */
class CatalogService
{
    public function __construct(
        private BranchScopeService $branchScope
    ) {}

    public function getPrograms(?string $branchId = null): Collection
    {
        $query = Program::query()->with('versions');

        if ($branchId && $branchId !== 'all') {
            $query->whereHas('branchProfiles', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        return $query->where('is_active', true)->get();
    }

    public function getProgramVersions(string $programId, ?string $branchId = null): Collection
    {
        $query = ProgramVersion::where('program_id', $programId)
            ->whereIn('status', ['published', 'draft']);

        return $query->orderBy('version_number', 'desc')->get();
    }

    public function getLevelsForVersion(string $programVersionId): Collection
    {
        return Level::where('program_version_id', $programVersionId)
            ->orderBy('order')
            ->with('subjects')
            ->get();
    }

    public function getActiveProgramVersionForBranch(string $branchId): ?ProgramVersion
    {
        $profile = DB::table('branch_academic_profiles')
            ->where('branch_id', $branchId)
            ->first();

        if ($profile && $profile->default_program_version_id) {
            return ProgramVersion::find($profile->default_program_version_id);
        }

        // Fallback to latest published
        return ProgramVersion::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->first();
    }

    public function publishProgramVersion(string $programVersionId, string $userId): ProgramVersion
    {
        $version = ProgramVersion::findOrFail($programVersionId);

        if ($version->status === 'published') {
            return $version;
        }

        DB::transaction(function () use ($version, $userId) {
            // Archive previous default
            ProgramVersion::where('program_id', $version->program_id)
                ->where('is_default', true)
                ->update(['is_default' => false, 'status' => 'archived']);

            $version->update([
                'status' => 'published',
                'published_at' => now(),
                'is_default' => true,
            ]);
        });

        return $version->fresh();
    }

    public function createProgramVersion(string $programId, array $data): ProgramVersion
    {
        $latest = ProgramVersion::where('program_id', $programId)
            ->orderBy('version_number', 'desc')
            ->first();

        $nextVersion = $latest ? $latest->version_number + 1 : 1;

        return ProgramVersion::create([
            'program_id' => $programId,
            'version_label' => $data['version_label'] ?? "v{$nextVersion}",
            'version_number' => $nextVersion,
            'status' => 'draft',
            'effective_from' => $data['effective_from'] ?? now()->toDateString(),
            'is_default' => false,
        ]);
    }
}
