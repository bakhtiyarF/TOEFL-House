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
        .header p {
            color: #666;
            font-size: 11px;
            margin: 5px 0 0 0;
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
            letter-spacing: 0.5px;
        }
        .summary-value {
            font-size: 20px;
            font-weight: bold;
            margin-top: 5px;
        }
        .income { color: #10b981; }
        .expense { color: #ef4444; }
        .net { color: #2563eb; }
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
        <p>Period: {{ $period['start'] }} to {{ $period['end'] }}</p>
    </div>

    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Total Income</div>
                <div class="summary-value income">{{ number_format($summary['total_income'], 2) }} AFN</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Expenses</div>
                <div class="summary-value expense">{{ number_format($summary['total_expenses'], 2) }} AFN</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Net Income</div>
                <div class="summary-value net">{{ number_format($summary['net_income'], 2) }} AFN</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Savings (5%)</div>
                <div class="summary-value">{{ number_format($summary['savings_rate'], 2) }} AFN</div>
            </div>
        </div>
    </div>

    @if(!empty($income_breakdown))
    <div class="section">
        <h2>Income Breakdown by Category</h2>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="text-right">Count</th>
                    <th class="text-right">Total (AFN)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($income_breakdown as $category => $data)
                <tr>
                    <td>{{ ucfirst($category) }}</td>
                    <td class="text-right">{{ $data['count'] }}</td>
                    <td class="text-right">{{ number_format($data['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if(!empty($expense_breakdown))
    <div class="section">
        <h2>Expense Breakdown by Category</h2>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="text-right">Count</th>
                    <th class="text-right">Total (AFN)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expense_breakdown as $category => $data)
                <tr>
                    <td>{{ ucfirst($category) }}</td>
                    <td class="text-right">{{ $data['count'] }}</td>
                    <td class="text-right">{{ number_format($data['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="section">
        <h2>Recent Transactions</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th class="text-right">Amount (AFN)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions->take(50) as $tx)
                <tr>
                    <td>{{ $tx->date }}</td>
                    <td>{{ ucfirst($tx->type) }}</td>
                    <td>{{ ucfirst($tx->category) }}</td>
                    <td>{{ $tx->description }}</td>
                    <td class="text-right {{ $tx->type === 'income' ? 'income' : 'expense' }}">
                        {{ $tx->type === 'income' ? '+' : '-' }}{{ number_format($tx->amount, 2) }}
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
