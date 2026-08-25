<?php

use App\Modules\Academic\Models\AcademicClass;
use App\Modules\Academic\Models\Enrollment;
use App\Modules\Academic\Models\Exam;
use App\Modules\Academic\Models\ExamResult;
use App\Modules\Academic\Models\Level;
use App\Modules\Academic\Models\Program;
use App\Modules\Academic\Models\PromotionRule;
use App\Modules\Academic\Models\Student;
use App\Modules\Iam\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Promotion Management (live via PromotionService)', function () {
    beforeEach(function () {
        $this->branch = Branch::factory()->create();
        $this->user = \App\Modules\Iam\Models\User::factory()->create(['branch_id' => $this->branch->id]);

        $this->program = Program::factory()->create(['branch_id' => $this->branch->id]);
        $this->levelFrom = Level::factory()->create(['program_id' => $this->program->id]);
        $this->levelTo = Level::factory()->create(['program_id' => $this->program->id]);

        $this->programVersion = \App\Modules\Academic\Models\ProgramVersion::factory()->create([
            'program_id' => $this->program->id,
        ]);

        PromotionRule::create([
            'program_version_id' => $this->programVersion->id,
            'from_level_id' => $this->levelFrom->id,
            'to_level_id' => $this->levelTo->id,
            'min_score' => 80,
            'min_attendance_pct' => 85,
            'auto_promote' => false,
            'branch_id' => $this->branch->id,
        ]);

        $this->student = Student::factory()->create(['branch_id' => $this->branch->id]);

        $this->class = AcademicClass::factory()->create([
            'branch_id' => $this->branch->id,
            'program_id' => $this->program->id,
            'level_id' => $this->levelFrom->id,
        ]);

        Enrollment::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'program_id' => $this->program->id,
            'program_version_id' => $this->programVersion->id,
            'level_id' => $this->levelFrom->id,
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'fee_snapshot_json' => json_encode(['fee_rules' => []]),
            'started_at' => now(),
        ]);

        // Give the student qualifying scores + attendance (via rosters)
        $exam = Exam::factory()->create(['class_id' => $this->class->id, 'branch_id' => $this->branch->id]);
        ExamResult::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'score' => 87,
        ]);
    });

    it('recommends promotion when student meets rules', function () {
        // Create some roster attendance to make rate high
        $session = \App\Modules\Academic\Models\Session::factory()->create(['class_id' => $this->class->id]);
        \App\Modules\Academic\Models\Roster::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'session_id' => $session->id,
            'student_id' => $this->student->id,
            'attendance_status' => 'present',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/students/{$this->student->id}/promotion-recommend?program_version_id={$this->programVersion->id}");

        $response->assertOk()
            ->assertJsonFragment(['can_promote' => true]);
    });

    it('applies promotion and creates journey event', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/students/{$this->student->id}/promote", [
                'to_level_id' => $this->levelTo->id,
                'from_level_id' => $this->levelFrom->id,
                'program_version_id' => $this->programVersion->id,
                'reason' => 'Test promotion',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('student_journey_events', [
            'student_id' => $this->student->id,
            'event_type' => 'PROMOTION_DECIDED',
        ]);

        $enrollment = Enrollment::where('student_id', $this->student->id)->first();
        expect($enrollment->level_id)->toBe($this->levelTo->id);
    });
});
