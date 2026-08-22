<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Enrollment Confirmation</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .enrollment-box {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #3b82f6;
        }
        .info-row {
            margin: 12px 0;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #666;
            display: block;
            margin-bottom: 5px;
        }
        .info-value {
            color: #333;
            font-size: 16px;
        }
        .fee-box {
            background: #fef3c7;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #f59e0b;
        }
        .fee-amount {
            font-size: 28px;
            font-weight: bold;
            color: #d97706;
            text-align: center;
            margin: 15px 0;
        }
        .schedule-box {
            background: #e0e7ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #6366f1;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #999;
        }
        .cta-button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Enrollment Confirmed!</h1>
        <p>You're all set for your learning journey</p>
    </div>

    <div class="content">
        <p>Dear <strong>{{ $enrollment->student->full_name }}</strong>,</p>

        <p>Congratulations! Your enrollment has been successfully confirmed. We're excited to have you in our program.</p>

        <div class="enrollment-box">
            <h3 style="margin-top: 0; color: #3b82f6;">Enrollment Details</h3>

            <div class="info-row">
                <span class="info-label">Student Code:</span>
                <span class="info-value"><strong>{{ $enrollment->student->student_code }}</strong></span>
            </div>

            <div class="info-row">
                <span class="info-label">Program:</span>
                <span class="info-value">{{ $enrollment->programVersion->program->name ?? 'General English' }}</span>
            </div>

            @if($enrollment->level)
            <div class="info-row">
                <span class="info-label">Level:</span>
                <span class="info-value">{{ $enrollment->level }}</span>
            </div>
            @endif

            <div class="info-row">
                <span class="info-label">Class:</span>
                <span class="info-value">{{ $enrollment->class->name ?? 'To be assigned' }}</span>
            </div>

            @if($enrollment->class && $enrollment->class->teacher)
            <div class="info-row">
                <span class="info-label">Teacher:</span>
                <span class="info-value">{{ $enrollment->class->teacher->full_name }}</span>
            </div>
            @endif

            <div class="info-row">
                <span class="info-label">Enrollment Type:</span>
                <span class="info-value">{{ ucfirst(str_replace('_', ' ', $enrollment->enrollment_type)) }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Start Date:</span>
                <span class="info-value">{{ $enrollment->started_at ? $enrollment->started_at->format('F j, Y') : 'To be announced' }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value" style="color: #10b981; font-weight: 600;">
                    {{ ucfirst($enrollment->status) }}
                </span>
            </div>
        </div>

        @if($enrollment->class && $enrollment->class->schedule_time)
        <div class="schedule-box">
            <h3 style="margin-top: 0; color: #6366f1;">Class Schedule</h3>
            <p style="font-size: 18px; margin: 10px 0;">
                <strong>{{ $enrollment->class->schedule_time }}</strong>
            </p>
            @if($enrollment->class->start_date && $enrollment->class->end_date)
            <p style="margin: 5px 0; color: #666;">
                {{ $enrollment->class->start_date->format('M j') }} - {{ $enrollment->class->end_date->format('M j, Y') }}
            </p>
            @endif
        </div>
        @endif

        <div class="fee-box">
            <h3 style="margin-top: 0; color: #d97706;">Fee Information</h3>
            @php
                $snapshot = $enrollment->fee_snapshot_json;
                $grossTuition = $snapshot['gross_tuition'] ?? $enrollment->class->fee ?? 0;
                $discountPercent = $snapshot['discount_percent'] ?? $enrollment->discount_percent ?? 0;
                $scholarshipPercent = $snapshot['scholarship_percent'] ?? $enrollment->scholarship_percent ?? 0;
                $netTuition = $snapshot['net_tuition'] ?? $grossTuition;
            @endphp

            <div class="fee-amount">
                {{ number_format($netTuition, 2) }} AFN
            </div>

            @if($discountPercent > 0 || $scholarshipPercent > 0)
            <div style="text-align: center; margin: 10px 0;">
                @if($discountPercent > 0)
                <p style="margin: 5px 0;">Discount: {{ $discountPercent }}%</p>
                @endif
                @if($scholarshipPercent > 0)
                <p style="margin: 5px 0;">Scholarship: {{ $scholarshipPercent }}%</p>
                @endif
            </div>
            @endif

            <p style="text-align: center; margin-top: 15px; font-size: 14px;">
                Please ensure your fees are paid before the start of classes.
            </p>
        </div>

        <p><strong>What's Next?</strong></p>
        <ul>
            <li>Complete your fee payment (if not already done)</li>
            <li>Collect your student ID card from the reception</li>
            <li>Attend your first class as per the schedule above</li>
            <li>Check your student portal for any updates or announcements</li>
        </ul>

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}" class="cta-button">View Student Portal</a>
        </div>

        <p>If you have any questions or need to make changes to your enrollment, please contact us:</p>
        <ul>
            <li>Phone: +93 700 000 000</li>
            <li>Email: registrar@toeflhouse.af</li>
            <li>Visit us: Main Campus, Kabul</li>
        </ul>

        <p>We look forward to seeing you in class!</p>

        <p>
            Best regards,<br>
            <strong>TOEFL House Registrar Office</strong>
        </p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} TOEFL House Educational Institute. All rights reserved.</p>
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</body>
</html>
