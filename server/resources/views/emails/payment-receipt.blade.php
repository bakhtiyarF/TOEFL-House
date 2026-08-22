<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt</title>
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
            background: #10b981;
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
        .receipt-box {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin: 20px 0;
            border: 2px solid #10b981;
        }
        .receipt-number {
            font-size: 24px;
            font-weight: bold;
            color: #10b981;
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: #f0fdf4;
            border-radius: 5px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #666;
        }
        .info-value {
            color: #333;
            text-align: right;
        }
        .amount {
            font-size: 32px;
            font-weight: bold;
            color: #10b981;
            text-align: center;
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
        .success-icon {
            font-size: 48px;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Payment Receipt</h1>
        <p>Thank you for your payment</p>
    </div>

    <div class="content">
        <div class="success-icon">✅</div>

        <p>Dear <strong>{{ $payment->student->full_name ?? 'Valued Student' }}</strong>,</p>

        <p>We have successfully received your payment. Below are the details of your transaction:</p>

        <div class="receipt-box">
            <div class="receipt-number">
                Receipt #: {{ $payment->receipt_number }}
            </div>

            <div class="amount">
                {{ number_format($payment->amount, 2) }} AFN
            </div>

            <div class="info-row">
                <span class="info-label">Payment Date:</span>
                <span class="info-value">{{ $payment->date->format('F j, Y') }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Payment Method:</span>
                <span class="info-value">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Category:</span>
                <span class="info-value">{{ ucfirst($payment->category) }}</span>
            </div>

            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value" style="color: #10b981; font-weight: 600;">
                    {{ ucfirst($payment->status) }}
                </span>
            </div>

            @if($payment->student)
            <div class="info-row">
                <span class="info-label">Student Code:</span>
                <span class="info-value">{{ $payment->student->student_code }}</span>
            </div>
            @endif

            @if($payment->notes)
            <div class="info-row">
                <span class="info-label">Notes:</span>
                <span class="info-value">{{ $payment->notes }}</span>
            </div>
            @endif
        </div>

        <p><strong>Important:</strong> Please keep this receipt for your records. Your receipt number <strong>{{ $payment->receipt_number }}</strong> may be required for future reference.</p>

        <p>If you have any questions about this payment or need assistance, please contact us:</p>
        <ul>
            <li>Phone: +93 700 000 000</li>
            <li>Email: finance@toeflhouse.af</li>
        </ul>

        <p>Thank you for your prompt payment!</p>

        <p>
            Best regards,<br>
            <strong>TOEFL House Finance Department</strong>
        </p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} TOEFL House Educational Institute. All rights reserved.</p>
        <p>This is an automated receipt. Please do not reply to this message.</p>
    </div>
</body>
</html>
