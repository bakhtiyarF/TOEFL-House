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
        .class-info {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-item {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 10px;
        }
        .info-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
        .info-value {
            font-size: 18px;
            font-weight: bold;
            margin-top: 5px;
            color: #2563eb;
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
            font-size: 10px;
            text-transform: uppercase;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .attendance-good { color: #10b981; font-weight: 600; }
        .attendance-warning { color: #f59e0b; font-weight: 600; }
        .attendance-poor { color: #ef4444; font-weight: 600; }
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
        <p>{{ $class->schedule ?? '' }}</p>
    </div>

    <div class="class-info">
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Total Students</div>
                <div class="info-value">{{ $total_students }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Capacity</div>
                <div class="info-value">{{ $class->capacity }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Avg Attendance</div>
                <div class="info-value">{{ $average_attendance }}%</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Student Roster</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student Code</th>
                    <th>Full Name</th>
                    <th>Phone</th>
                    <th>Enroll Date</th>
                    <th>Sessions</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Attendance %</th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $index => $student)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $student->student_code }}</td>
                    <td>{{ $student->full_name }}</td>
                    <td>{{ $student->phone ?? 'N/A' }}</td>
                    <td>{{ $student->enroll_date }}</td>
                    <td>{{ $student->total_sessions }}</td>
                    <td>{{ $student->present }}</td>
                    <td>{{ $student->absent }}</td>
                    <td class="{{ $student->attendance_rate >= 85 ? 'attendance-good' : ($student->attendance_rate >= 70 ? 'attendance-warning' : 'attendance-poor') }}">
                        {{ $student->attendance_rate }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Generated on {{ $generated_at }} | TOEFL House ERP v3</p>
    </div>
</body>
</html>
