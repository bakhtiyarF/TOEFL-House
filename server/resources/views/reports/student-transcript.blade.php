<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            font-size: 24px;
            margin: 0;
        }
        .student-info {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: 600;
            padding: 5px 20px 5px 0;
            color: #666;
            width: 30%;
        }
        .info-value {
            display: table-cell;
            padding: 5px 0;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #1f2937;
            font-size: 16px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background: #f9fafb;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-completed { background: #dbeafe; color: #1e40af; }
        .status-dropped { background: #fee2e2; color: #991b1b; }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #999;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Student Code: {{ $student->student_code }}</p>
    </div>

    <div class="student-info">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Full Name:</div>
                <div class="info-value">{{ $student->full_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Father's Name:</div>
                <div class="info-value">{{ $student->father_name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Phone:</div>
                <div class="info-value">{{ $student->phone ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Registration Date:</div>
                <div class="info-value">{{ $student->registration_date }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Current Status:</div>
                <div class="info-value">
                    <span class="status-badge status-{{ $student->status }}">{{ ucfirst($student->status) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Enrollment History</h2>
        <table>
            <thead>
                <tr>
                    <th>Program</th>
                    <th>Level</th>
                    <th>Class</th>
                    <th>Teacher</th>
                    <th>Start Date</th>
                    <th>Attendance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($enrollments as $enrollment)
                <tr>
                    <td>{{ $enrollment['program'] }}</td>
                    <td>{{ $enrollment['level'] ?? 'N/A' }}</td>
                    <td>{{ $enrollment['class'] }}</td>
                    <td>{{ $enrollment['teacher'] }}</td>
                    <td>{{ $enrollment['start_date'] }}</td>
                    <td>{{ $enrollment['attendance_rate'] }}%</td>
                    <td>
                        <span class="status-badge status-{{ $enrollment['status'] }}">
                            {{ ucfirst($enrollment['status']) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($journey_events->count() > 0)
    <div class="section">
        <h2>Recent Journey Events</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Event Type</th>
                    <th>Description</th>
                    <th>Actor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($journey_events as $event)
                <tr>
                    <td>{{ $event->occurred_at->format('Y-m-d H:i') }}</td>
                    <td>{{ str_replace('_', ' ', $event->event_type) }}</td>
                    <td>{{ json_encode($event->payload) }}</td>
                    <td>{{ $event->actor_name ?? 'System' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Generated on {{ $generated_at }} | TOEFL House ERP v3</p>
    </div>
</body>
</html>
