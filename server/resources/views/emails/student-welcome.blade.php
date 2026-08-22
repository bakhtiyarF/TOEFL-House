<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to TOEFL House</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .student-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }
        .info-row {
            margin: 10px 0;
        }
        .info-label {
            font-weight: 600;
            color: #666;
            display: inline-block;
            width: 150px;
        }
        .info-value {
            color: #333;
        }
        .cta-button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #999;
        }
        .next-steps {
            background: #e8f4f8;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .next-steps h3 {
            margin-top: 0;
            color: #2c5282;
        }
        .next-steps ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .next-steps li {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to TOEFL House!</h1>
        <p>We're excited to have you join our learning community</p>
    </div>

    <div class="content">
        <p>Dear <strong>{{ $student->full_name }}</strong>,</p>

        <p>Welcome to TOEFL House! We're thrilled to have you as part of our educational community. Your registration has been successfully completed.</p>

        <div class="student-info">
            <h3 style="margin-top: 0;">Your Student Information</h3>
            <div class="info-row">
                <span class="info-label">Student Code:</span>
                <span class="info-value"><strong>{{ $student->student_code }}</strong></span>
            </div>
            <div class="info-row">
                <span class="info-label">Registration Date:</span>
                <span class="info-value">{{ $student->registration_date->format('F j, Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span class="info-value">{{ $student->phone ?? 'Not provided' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value">{{ $student->email ?? 'Not provided' }}</span>
            </div>
            @if($student->placement_score)
            <div class="info-row">
                <span class="info-label">Placement Score:</span>
                <span class="info-value">{{ $student->placement_score['score'] ?? 'N/A' }}</span>
            </div>
            @endif
        </div>

        <div class="next-steps">
            <h3>Next Steps</h3>
            <ul>
                <li>Complete your enrollment in a program (if not already done)</li>
                <li>Pay your tuition fees to secure your spot</li>
                <li>Attend your scheduled classes</li>
                <li>Check your student portal regularly for updates</li>
            </ul>
        </div>

        <p>Your student code <strong>{{ $student->student_code }}</strong> will be used for all future communications and transactions. Please keep it safe.</p>

        <p>If you have any questions or need assistance, please don't hesitate to contact us:</p>
        <ul>
            <li>Phone: +93 700 000 000</li>
            <li>Email: info@toeflhouse.af</li>
            <li>Visit us: Main Campus, Kabul</li>
        </ul>

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}" class="cta-button">Visit Student Portal</a>
        </div>

        <p>We look forward to supporting you on your educational journey!</p>

        <p>
            Best regards,<br>
            <strong>The TOEFL House Team</strong>
        </p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} TOEFL House Educational Institute. All rights reserved.</p>
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</body>
</html>
