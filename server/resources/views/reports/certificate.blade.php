<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 2cm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            text-align: center;
            color: #1f2937;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
        }
        .certificate-container {
            background: white;
            border: 8px solid #d4af37;
            padding: 60px 40px;
            position: relative;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .certificate-border {
            border: 3px solid #d4af37;
            padding: 40px;
            position: relative;
        }
        .certificate-header {
            margin-bottom: 40px;
        }
        .certificate-header h1 {
            font-size: 42px;
            color: #d4af37;
            margin: 0;
            font-weight: 300;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .certificate-header h2 {
            font-size: 24px;
            color: #666;
            margin: 10px 0 0 0;
            font-weight: 400;
        }
        .certificate-body {
            margin: 40px 0;
        }
        .presented-to {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 20px;
        }
        .student-name {
            font-size: 48px;
            color: #1f2937;
            font-weight: 600;
            margin: 20px 0;
            border-bottom: 2px solid #d4af37;
            display: inline-block;
            padding: 0 40px 10px 40px;
        }
        .completion-text {
            font-size: 16px;
            color: #666;
            line-height: 1.8;
            margin: 30px auto;
            max-width: 600px;
        }
        .program-details {
            font-size: 18px;
            color: #2563eb;
            font-weight: 600;
            margin: 20px 0;
        }
        .certificate-footer {
            margin-top: 60px;
            display: table;
            width: 100%;
        }
        .signature-block {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 20px;
        }
        .signature-line {
            border-top: 2px solid #333;
            margin: 20px auto;
            width: 200px;
        }
        .signature-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .certificate-number {
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 10px;
            color: #999;
        }
        .issue-date {
            position: absolute;
            bottom: 20px;
            left: 20px;
            font-size: 10px;
            color: #999;
        }
        /* Template-specific overrides */
        .template-modern .certificate-container { border-color: #334155; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1); }
        .template-modern .certificate-header h1 { color: #334155; }
        .template-minimal .certificate-container { border: 1px solid #e5e7eb; padding: 40px; box-shadow: none; background: #fff; }
        .template-minimal .student-name { font-size: 32px; border: none; }
        .template-minimal .certificate-header h1 { font-size: 28px; letter-spacing: 1px; }
    </style>
</head>
<body class="template-{{ $template ?? 'classic' }}">
    <div class="certificate-container">
        <div class="certificate-border">
            <div class="certificate-header">
                <h1>Certificate of Completion</h1>
                <h2>TOEFL House Educational Institute</h2>
            </div>

            <div class="certificate-body">
                <div class="presented-to">This is to certify that</div>
                <div class="student-name">{{ $student->full_name }}</div>
                
                <div class="completion-text">
                    has successfully completed the requirements for
                </div>

                <div class="program-details">
                    {{ $program->name ?? 'General English Program' }}
                    @if($level)
                        <br>{{ $level->name }}
                    @endif
                </div>

                <div class="completion-text">
                    with a grade of <strong>{{ $certificate->grade ?? 'Pass' }}</strong>
                </div>
            </div>

            <div class="certificate-footer">
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <div class="signature-label">Director</div>
                </div>
                <div class="signature-block">
                    <div class="signature-line"></div>
                    <div class="signature-label">Academic Coordinator</div>
                </div>
            </div>

            <div class="issue-date">
                Issued: {{ $certificate->issue_date }}
            </div>
            <div class="certificate-number">
                Certificate No: {{ $certificate->certificate_no }} 
                @if(isset($template)) · {{ strtoupper($template) }} @endif
            </div>
        </div>
    </div>
    <div style="text-align:center; margin-top:12px; font-size:10px; color:#888;">Template: {{ $template ?? 'classic' }} — Generated by ReportGenerationService</div>
</body>
</html>
