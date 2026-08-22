<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Student channels
Broadcast::channel('student.{studentId}', function ($user, $studentId) {
    return $user->student && $user->student->id === $studentId;
});

// Teacher channels
Broadcast::channel('teacher.{teacherId}', function ($user, $teacherId) {
    return $user->teacher && $user->teacher->id === $teacherId;
});

// Class channels
Broadcast::channel('class.{classId}', function ($user, $classId) {
    $class = \App\Modules\Academic\Models\AcademicClass::find($classId);
    
    if (!$class) {
        return false;
    }

    // Check if user is enrolled in the class
    if ($user->student) {
        return $class->students->contains($user->student->id);
    }

    // Check if user is the teacher
    if ($user->teacher) {
        return $class->teacher_id === $user->teacher->id;
    }

    // Check if user has admin access
    return $user->can('view', $class);
});

// Session channels
Broadcast::channel('session.{sessionId}', function ($user, $sessionId) {
    $session = \App\Modules\Academic\Models\Session::find($sessionId);
    
    if (!$session) {
        return false;
    }

    $class = $session->class;

    // Check if user is enrolled in the class
    if ($user->student) {
        return $class->students->contains($user->student->id);
    }

    // Check if user is the teacher
    if ($user->teacher) {
        return $class->teacher_id === $user->teacher->id;
    }

    // Check if user has admin access
    return $user->can('view', $class);
});

// Exam channels
Broadcast::channel('exam.{examId}', function ($user, $examId) {
    $exam = \App\Modules\Academic\Models\Exam::find($examId);
    
    if (!$exam) {
        return false;
    }

    $class = $exam->class;

    // Check if user is enrolled in the class
    if ($user->student) {
        return $class->students->contains($user->student->id);
    }

    // Check if user is the teacher
    if ($user->teacher) {
        return $class->teacher_id === $user->teacher->id;
    }

    // Check if user has admin access
    return $user->can('view', $exam);
});

// Homework channels
Broadcast::channel('homework.{homeworkId}', function ($user, $homeworkId) {
    $homework = \App\Modules\Academic\Models\Homework::find($homeworkId);
    
    if (!$homework) {
        return false;
    }

    $class = $homework->class;

    // Check if user is enrolled in the class
    if ($user->student) {
        return $class->students->contains($user->student->id);
    }

    // Check if user is the teacher
    if ($user->teacher) {
        return $class->teacher_id === $user->teacher->id;
    }

    // Check if user has admin access
    return $user->can('view', $homework);
});

// Branch channels
Broadcast::channel('branch.{branchId}', function ($user, $branchId) {
    return $user->branch_id === $branchId || $user->can('manageBranch', \App\Modules\Iam\Models\Branch::find($branchId));
});

// Enrollment channels
Broadcast::channel('enrollment.{enrollmentId}', function ($user, $enrollmentId) {
    $enrollment = \App\Modules\Academic\Models\Enrollment::find($enrollmentId);
    
    if (!$enrollment) {
        return false;
    }

    // Check if user is the student
    if ($user->student) {
        return $enrollment->student_id === $user->student->id;
    }

    // Check if user has admin access
    return $user->can('view', $enrollment);
});

// Attendance channels
Broadcast::channel('attendance.{classId}', function ($user, $classId) {
    $class = \App\Modules\Academic\Models\AcademicClass::find($classId);
    
    if (!$class) {
        return false;
    }

    // Check if user is enrolled in the class
    if ($user->student) {
        return $class->students->contains($user->student->id);
    }

    // Check if user is the teacher
    if ($user->teacher) {
        return $class->teacher_id === $user->teacher->id;
    }

    // Check if user has admin access
    return $user->can('view', $class);
});

// Grade channels
Broadcast::channel('grade.{studentId}', function ($user, $studentId) {
    // Check if user is the student
    if ($user->student) {
        return $user->student->id === $studentId;
    }

    // Check if user has admin access
    return $user->can('viewGrades', \App\Modules\Academic\Models\Student::find($studentId));
});

// Payment channels
Broadcast::channel('payment.{studentId}', function ($user, $studentId) {
    // Check if user is the student
    if ($user->student) {
        return $user->student->id === $studentId;
    }

    // Check if user has admin access
    return $user->can('viewPayments', \App\Modules\Academic\Models\Student::find($studentId));
});

// Notification channels
Broadcast::channel('notification.{userId}', function ($user, $userId) {
    return $user->id === $userId;
});

// General channels
Broadcast::channel('students', function ($user) {
    return $user->can('viewAny', \App\Modules\Academic\Models\Student::class);
});

Broadcast::channel('teachers', function ($user) {
    return $user->can('viewAny', \App\Modules\PeopleHr\Models\Teacher::class);
});

Broadcast::channel('classes', function ($user) {
    return $user->can('viewAny', \App\Modules\Academic\Models\AcademicClass::class);
});

Broadcast::channel('exams', function ($user) {
    return $user->can('viewAny', \App\Modules\Academic\Models\Exam::class);
});

Broadcast::channel('homework', function ($user) {
    return $user->can('viewAny', \App\Modules\Academic\Models\Homework::class);
});

Broadcast::channel('branches', function ($user) {
    return $user->can('viewAny', \App\Modules\Iam\Models\Branch::class);
});

Broadcast::channel('enrollments', function ($user) {
    return $user->can('viewAny', \App\Modules\Academic\Models\Enrollment::class);
});

Broadcast::channel('invoices', function ($user) {
    return $user->can('viewAny', \App\Modules\FinancePayroll\Models\Invoice::class);
});

Broadcast::channel('payments', function ($user) {
    return $user->can('viewAny', \App\Modules\FinancePayroll\Models\Payment::class);
});

Broadcast::channel('donations', function ($user) {
    return $user->can('viewAny', \App\Modules\FundingImpact\Models\Donation::class);
});

Broadcast::channel('campaigns', function ($user) {
    return $user->can('viewAny', \App\Modules\FundingImpact\Models\Campaign::class);
});

Broadcast::channel('certificates', function ($user) {
    return $user->can('viewAny', \App\Modules\Academic\Models\Certificate::class);
});

Broadcast::channel('finance', function ($user) {
    return $user->can('viewFinancialReports');
});

Broadcast::channel('funding', function ($user) {
    return $user->can('viewFundingReports');
});
