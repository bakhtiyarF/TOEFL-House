<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Your Password</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .reset-button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 14px 40px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            margin: 20px 0;
        }
        .reset-button:hover {
            background: #5568d3;
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
            <h1>🔐 Password Reset Request</h1>
        </div>

        <div class="content">
            <p>Hello <strong>{{ $user->full_name }}</strong>,</p>

            <p>We received a request to reset the password for your TOEFL House account. If you made this request, click the button below to reset your password:</p>

            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="reset-button">Reset Password</a>
            </div>

            <div class="warning">
                <strong>⏰ This link will expire in {{ $expirationMinutes }} minutes.</strong>
                <p style="margin: 10px 0 0 0;">For security reasons, this reset link can only be used once and will expire after {{ $expirationMinutes }} minutes.</p>
            </div>

            <p>If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.</p>

            <div class="info-box">
                <strong>🔒 Security Tips:</strong>
                <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                    <li>Never share your password with anyone</li>
                    <li>Use a strong, unique password</li>
                    <li>Consider enabling two-factor authentication</li>
                    <li>If you suspect unauthorized access, contact us immediately</li>
                </ul>
            </div>

            <p>If the button above doesn't work, copy and paste this link into your browser:</p>
            <div class="code-box">{{ $resetUrl }}</div>

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
