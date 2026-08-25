<?php

use App\Modules\Academic\Models\AcademicClass;
use App\Modules\Academic\Models\Homework;
use App\Modules\Academic\Models\Exam;
use App\Modules\Academic\Models\Session;
use App\Modules\Academic\Models\Student;
use App\Modules\Iam\Models\Branch;
use App\Modules\PeopleHr\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Homework & Exam Management', function () {
    beforeEach(function () {
        $this->branch = Branch::factory()->create();
        $this->user = \App\Modules\Iam\Models\User::factory()->create([
            'branch_id' => $this->branch->id,
            'role' => 'teacher',
        ]);

        $this->class = AcademicClass::factory()->create([
            'branch_id' => $this->branch->id,
            'status' => 'active',
        ]);

        $this->session = Session::factory()->create([
            'class_id' => $this->class->id,
            'date' => now()->toDateString(),
        ]);

        $this->student = Student::factory()->create(['branch_id' => $this->branch->id]);
    });

    it('can create homework for a class session', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/classes/{$this->class->id}/homework", [
                'session_id' => $this->session->id,
                'title' => 'Unit 5 Reading',
                'description' => 'Complete pages 42-45',
                'due_date' => now()->addDays(3)->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'Unit 5 Reading']);

        $this->assertDatabaseHas('homework', [
            'session_id' => $this->session->id,
            'title' => 'Unit 5 Reading',
        ]);
    });

    it('can list homework for a class', function () {
        Homework::factory()->count(3)->create([
            'session_id' => $this->session->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/classes/{$this->class->id}/homework");

        $response->assertOk()
            ->assertJsonCount(3);
    });

    it('can create an exam for a class', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/classes/{$this->class->id}/exams", [
                'title' => 'Midterm Exam',
                'date' => now()->addDays(10)->toDateString(),
                'fee' => 1500,
                'type' => 'midterm',
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'Midterm Exam', 'type' => 'midterm']);

        $this->assertDatabaseHas('exams', [
            'class_id' => $this->class->id,
            'type' => 'midterm',
        ]);
    });

    it('can record exam results', function () {
        $exam = Exam::factory()->create([
            'class_id' => $this->class->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/exams/{$exam->id}/results", [
                'student_id' => $this->student->id,
                'score' => 87.5,
                'exam_fee_paid' => true,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('exam_results', [
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'score' => 87.5,
        ]);
    });

    it('enforces branch scoping on homework/exams', function () {
        $otherBranch = Branch::factory()->create();
        $otherClass = AcademicClass::factory()->create(['branch_id' => $otherBranch->id]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/classes/{$otherClass->id}/homework")
            ->assertStatus(403);
    });
});
