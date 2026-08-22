<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your Verification Code</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .code-display {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 3px solid #f59e0b;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
            border-radius: 10px;
        }
        .code {
            font-size: 48px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            letter-spacing: 8px;
            color: #92400e;
            margin: 0;
        }
        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #0ea5e9;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            background: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Two-Factor Authentication</h1>
        </div>

        <div class="content">
            <p>Hello <strong>{{ $user->full_name }}</strong>,</p>

            <p>A login attempt was detected for your TOEFL House account. To complete the login process, please use the verification code below:</p>

            <div class="code-display">
                <p class="code">{{ $code }}</p>
            </div>

            <div class="warning">
                <strong>⏰ This code will expire in {{ $expirationMinutes }} minutes.</strong>
                <p style="margin: 10px 0 0 0;">For security reasons, this code can only be used once and will expire after {{ $expirationMinutes }} minutes.</p>
            </div>

            <div class="info-box">
                <strong>🔒 Security Notice:</strong>
                <p style="margin: 10px 0 0 0;">If you didn't attempt to log in, someone may be trying to access your account. Please:</p>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li>Change your password immediately</li>
                    <li>Review your account activity</li>
                    <li>Contact our support team if you suspect unauthorized access</li>
                </ul>
            </div>

            <p><strong>Login Details:</strong></p>
            <ul>
                <li>Time: {{ now()->format('F j, Y g:i A') }}</li>
                <li>Account: {{ $user->email }}</li>
            </ul>

            <p>
                Need help? Contact our support team:<br>
                📧 support@toeflhouse.af<br>
                📞 +93 700 000 000
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} TOEFL House Educational Institute. All rights reserved.</p>
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>If you have any questions, contact us at support@toeflhouse.af</p>
        </div>
    </div>
</body>
</html>
