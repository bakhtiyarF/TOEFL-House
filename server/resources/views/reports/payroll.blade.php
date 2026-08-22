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
        .summary-box {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-item {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 10px;
        }
        .summary-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
        .summary-value {
            font-size: 20px;
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
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .text-right {
            text-align: right;
        }
        .status-paid { color: #10b981; font-weight: 600; }
        .status-partial { color: #f59e0b; font-weight: 600; }
        .status-unpaid { color: #ef4444; font-weight: 600; }
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
        <p>Period: {{ $period }}</p>
    </div>

    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Total Teachers</div>
                <div class="summary-value">{{ $summary['total_teachers'] }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Due</div>
                <div class="summary-value">{{ number_format($summary['total_due'], 2) }} AFN</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Paid</div>
                <div class="summary-value">{{ number_format($summary['total_paid'], 2) }} AFN</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Remaining</div>
                <div class="summary-value">{{ number_format($summary['total_remaining'], 2) }} AFN</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Teacher Payroll Breakdown</h2>
        <table>
            <thead>
                <tr>
                    <th>Teacher Name</th>
                    <th>Salary Type</th>
                    <th>Period</th>
                    <th class="text-right">Due Amount</th>
                    <th class="text-right">Paid Amount</th>
                    <th class="text-right">Remaining</th>
                    <th>Status</th>
                    <th>Paid Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($breakdown as $entry)
                @php
                    $remaining = $entry->due_amount - $entry->paid_amount;
                    $status = $remaining <= 0 ? 'paid' : ($entry->paid_amount > 0 ? 'partial' : 'unpaid');
                @endphp
                <tr>
                    <td>{{ $entry->full_name }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $entry->salary_type)) }}</td>
                    <td>{{ $entry->period_label }}</td>
                    <td class="text-right">{{ number_format($entry->due_amount, 2) }}</td>
                    <td class="text-right">{{ number_format($entry->paid_amount, 2) }}</td>
                    <td class="text-right">{{ number_format($remaining, 2) }}</td>
                    <td class="status-{{ $status }}">{{ ucfirst($status) }}</td>
                    <td>{{ $entry->paid_at ? date('Y-m-d', strtotime($entry->paid_at)) : 'N/A' }}</td>
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
