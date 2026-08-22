<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verify Your Email</title>
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
        .verify-button {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 14px 40px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            margin: 20px 0;
        }
        .verify-button:hover {
            background: #059669;
        }
        .success-box {
            background: #d1fae5;
            border-left: 4px solid #10b981;
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
        .code-box {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            word-break: break-all;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✉️ Verify Your Email Address</h1>
        </div>

        <div class="content">
            <p>Welcome to TOEFL House, <strong>{{ $user->full_name }}</strong>!</p>

            <p>Thank you for registering with us. To complete your registration and verify your email address, please click the button below:</p>

            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="verify-button">Verify Email Address</a>
            </div>

            <div class="success-box">
                <strong>✅ Why verify your email?</strong>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li>Secure your account</li>
                    <li>Receive important notifications</li>
                    <li>Access all features of your account</li>
                    <li>Reset your password if needed</li>
                </ul>
            </div>

            <div class="info-box">
                <strong>⏰ This link will expire in {{ $expirationMinutes }} minutes.</strong>
                <p style="margin: 10px 0 0 0;">For security reasons, this verification link can only be used once.</p>
            </div>

            <p>If you didn't create an account with TOEFL House, you can safely ignore this email.</p>

            <p>If the button above doesn't work, copy and paste this link into your browser:</p>
            <div class="code-box">{{ $verificationUrl }}</div>

            <p>
                <strong>What's next?</strong><br>
                Once verified, you'll be able to:
            </p>
            <ul>
                <li>Access your student dashboard</li>
                <li>View your class schedule</li>
                <li>Track your attendance and progress</li>
                <li>Make payments and view receipts</li>
                <li>Receive important notifications</li>
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
