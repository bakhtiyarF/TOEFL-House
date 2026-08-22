<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Iam\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SessionController extends Controller
{
    public function __construct(
        private BranchScopeService $branchScopeService
    ) {}

    /**
     * List sessions for a class
     */
    public function index(Request $request, string $classId): JsonResponse
    {
        $sessions = DB::table('sessions')
            ->where('class_id', $classId)
            ->orderByDesc('date')
            ->get()
            ->map(function ($session) {
                $attendance = DB::table('rosters')
                    ->where('session_id', $session->id)
                    ->selectRaw("
                        COUNT(*) as total,
                        SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) as absent,
                        SUM(CASE WHEN attendance_status = 'sick' THEN 1 ELSE 0 END) as sick,
                        SUM(CASE WHEN attendance_status = 'leave' THEN 1 ELSE 0 END) as on_leave
                    ")
                    ->first();

                return [...((array)$session), 'attendance' => $attendance];
            });

        return response()->json($sessions);
    }

    /**
     * Create a session for a class
     */
    public function store(Request $request, string $classId): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'topic' => 'nullable|string|max:500',
            'teacher_id' => 'nullable|uuid',
        ]);

        $id = Str::uuid()->toString();
        DB::table('sessions')->insert([
            'id' => $id,
            'class_id' => $classId,
            ...$validated,
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Auto-create roster entries for all active students in this class
        $students = DB::table('student_semesters')
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->pluck('student_id');

        $rosterEntries = $students->map(fn($studentId) => [
            'id' => Str::uuid()->toString(),
            'session_id' => $id,
            'student_id' => $studentId,
            'attendance_status' => 'not_marked',
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        if (!empty($rosterEntries)) {
            DB::table('rosters')->insert($rosterEntries);
        }

        return response()->json(DB::table('sessions')->where('id', $id)->first(), 201);
    }

    /**
     * Get roster for a session
     */
    public function roster(string $sessionId): JsonResponse
    {
        $roster = DB::table('rosters as r')
            ->join('students as s', 'r.student_id', '=', 's.id')
            ->where('r.session_id', $sessionId)
            ->select('r.*', 's.student_code', 's.full_name', 's.gender')
            ->orderBy('s.full_name')
            ->get();

        return response()->json($roster);
    }

    /**
     * Update attendance for a session (bulk)
     */
    public function updateAttendance(Request $request, string $sessionId): JsonResponse
    {
        $validated = $request->validate([
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|uuid',
            'attendance.*.status' => 'required|in:present,absent,sick,leave',
        ]);

        foreach ($validated['attendance'] as $entry) {
            DB::table('rosters')
                ->where('session_id', $sessionId)
                ->where('student_id', $entry['student_id'])
                ->update([
                    'attendance_status' => $entry['status'],
                    'marked_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        // Mark session as completed
        DB::table('sessions')->where('id', $sessionId)->update([
            'status' => 'completed',
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Attendance saved', 'count' => count($validated['attendance'])]);
    }
}
