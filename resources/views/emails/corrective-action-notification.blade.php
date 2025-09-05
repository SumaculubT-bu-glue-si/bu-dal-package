<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>是正措置が必要 / Corrective Action Required</title>
    <style>
        body {
            font-family: 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }

        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
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
            font-weight: 600;
        }

        .header .subtitle {
            margin-top: 10px;
            opacity: 0.9;
            font-size: 16px;
        }

        .content {
            padding: 30px;
        }

        .alert-box {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #dc2626;
        }

        .alert-box h2 {
            color: #dc2626;
            margin: 0 0 10px 0;
            font-size: 18px;
        }

        .asset-info {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }

        .asset-info h3 {
            margin: 0 0 15px 0;
            color: #1e293b;
            font-size: 16px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: 600;
            color: #64748b;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .info-value {
            color: #1e293b;
            font-size: 14px;
        }

        .priority-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: white;
        }

        .priority-critical {
            background-color: #dc2626;
        }

        .priority-high {
            background-color: #ea580c;
        }

        .priority-medium {
            background-color: #ca8a04;
        }

        .priority-low {
            background-color: #059669;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: white;
        }

        .status-pending {
            background-color: #6b7280;
        }

        .status-in_progress {
            background-color: #ca8a04;
        }

        .status-overdue {
            background-color: #dc2626;
        }

        .status-completed {
            background-color: #059669;
        }

        .action-details {
            background-color: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }

        .action-details h3 {
            margin: 0 0 15px 0;
            color: #0c4a6e;
            font-size: 16px;
        }

        .issue-description {
            background-color: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }

        .issue-description h4 {
            margin: 0 0 10px 0;
            color: #c2410c;
            font-size: 14px;
        }

        .issue-text {
            color: #c2410c;
            font-size: 14px;
            line-height: 1.5;
        }

        .required-action {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }

        .required-action h4 {
            margin: 0 0 10px 0;
            color: #166534;
            font-size: 14px;
        }

        .action-text {
            color: #166534;
            font-size: 14px;
            line-height: 1.5;
        }

        .deadline-info {
            background-color: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }

        .deadline-info h4 {
            margin: 0 0 10px 0;
            color: #92400e;
            font-size: 14px;
        }

        .deadline-text {
            color: #92400e;
            font-size: 14px;
            font-weight: 600;
        }

        .footer {
            background-color: #f1f5f9;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }

        .cta-button {
            display: inline-block;
            background-color: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }

        .cta-button:hover {
            background-color: #2563eb;
        }

        .urgent-warning {
            background-color: #fef2f2;
            border: 2px solid #dc2626;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }

        .urgent-warning h2 {
            color: #dc2626;
            margin: 0 0 10px 0;
            font-size: 20px;
        }

        .urgent-warning p {
            color: #dc2626;
            font-weight: 600;
            margin: 0;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="header">
            <h1>🚨 是正措置が必要 / Corrective Action Required</h1>
            <div class="subtitle">監査中に資産の不整合が発見されました / Asset Discrepancy Found During Audit</div>
        </div>

        <div class="content">
            <div class="alert-box">
                <h2>⚠️ 即座のアクションが必要 / Immediate Action Required</h2>
                <p>割り当てられた資産の監査中に不整合が発見されました。以下の詳細を確認し、必要な是正措置を講じてください。</p>
                <p>A discrepancy has been found during the audit of your assigned asset. Please review the details below and take the necessary corrective action.</p>
            </div>

            <div class="asset-info">
                <h3>📋 資産情報 / Asset Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">資産ID / Asset ID</div>
                        <div class="info-value">{{ $asset->asset_id }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">種類 / Type</div>
                        <div class="info-value">{{ $asset->type ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">場所 / Location</div>
                        <div class="info-value">{{ $asset->location ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">現在のステータス / Current Status</div>
                        <div class="info-value">{{ $auditAsset->current_status ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-item">
                            <div class="info-label">優先度 / Priority</div>
                            <div class="info-value">
                                <span class="priority-badge priority-{{ $correctiveAction->priority }}">
                                    {{ ucfirst($correctiveAction->priority) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">ステータス / Status</div>
                        <div class="info-value">
                            <span class="status-badge status-{{ $correctiveAction->status }}">
                                {{ ucfirst(str_replace('_', ' ', $correctiveAction->status)) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="action-details">
                <h3>📝 是正措置詳細 / Corrective Action Details</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">監査計画 / Audit Plan</div>
                        <div class="info-value">{{ $auditPlan->name }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">期限 / Due Date</div>
                        <div class="info-value">
                            @if($correctiveAction->due_date)
                            {{ \Carbon\Carbon::parse($correctiveAction->due_date)->format('Y年n月j日 / M d, Y') }}
                            @else
                            未指定 / Not specified
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="issue-description">
                <h4>❌ 発見された問題 / Issue Found</h4>
                <div class="issue-text">{{ $correctiveAction->issue }}</div>
            </div>

            <div class="required-action">
                <h4>✅ 必要なアクション / Required Action</h4>
                <div class="action-text">{{ $correctiveAction->action }}</div>
            </div>

            @if($correctiveAction->due_date)
            @php
            $daysRemaining = \Carbon\Carbon::now()->diffInDays($correctiveAction->due_date, false);
            @endphp

            @if($daysRemaining <= 0)
                <div class="urgent-warning">
                <h2>🚨 期限超過！ / OVERDUE!</h2>
                <p>この是正措置は{{ abs($daysRemaining) }}日遅れています！</p>
                <p>This corrective action is {{ abs($daysRemaining) }} day(s) overdue!</p>
        </div>
        @elseif($daysRemaining <= 3)
            <div class="deadline-info">
            <h4>⏰ 緊急期限 / Urgent Deadline</h4>
            <div class="deadline-text">このアクションは{{ $daysRemaining }}日以内に期限です。このタスクを優先してください。</div>
            <div class="deadline-text">This action is due in {{ $daysRemaining }} day(s). Please prioritize this task.</div>
    </div>
    @else
    <div class="deadline-info">
        <h4>📅 期限リマインダー / Deadline Reminder</h4>
        <div class="deadline-text">このアクションは{{ $daysRemaining }}日以内に期限です。</div>
        <div class="deadline-text">This action is due in {{ $daysRemaining }} day(s).</div>
    </div>
    @endif
    @endif

    @if($correctiveAction->notes)
    <div class="action-details">
        <h3>📋 追加メモ / Additional Notes</h3>
        <p>{{ $correctiveAction->notes }}</p>
    </div>
    @endif

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ url('/audits/corrective-actions') }}" class="cta-button">
            是正措置を確認 / View Corrective Actions
        </a>
    </div>
    </div>

    <div class="footer">
        <p><strong>従業員 / Employee:</strong> {{ $employee->name }} ({{ $employee->email }})</p>
        <p><strong>資産 / Asset:</strong> {{ $asset->asset_id }} - {{ $asset->type ?? 'N/A' }}</p>
        <p><strong>監査計画 / Audit Plan:</strong> {{ $auditPlan->name }}</p>
        <p><strong>生成日時 / Generated:</strong> {{ \Carbon\Carbon::now()->format('Y年n月j日 G時i分 / M d, Y \a\t g:i A') }}</p>
        <p style="margin-top: 15px; font-size: 12px; color: #94a3b8;">
            これは資産監査システムからの自動通知です。このメールには返信しないでください。<br>
            This is an automated notification from the Asset Audit System. Please do not reply to this email.
        </p>
    </div>
    </div>
</body>

</html>