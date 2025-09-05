<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>是正措置サマリー / Corrective Actions Summary</title>
    <style>
        body {
            font-family: 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9fafb;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }

        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .summary-stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 25px;
            text-align: center;
        }

        .stat-item {
            flex: 1;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            display: block;
        }

        .stat-label {
            color: #6b7280;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .actions-list {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .action-item {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f9fafb;
        }

        .action-item:last-child {
            margin-bottom: 0;
        }

        .action-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .asset-info {
            font-weight: 600;
            color: #1f2937;
            font-size: 16px;
        }

        .priority-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .priority-critical {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .priority-high {
            background: #fffbeb;
            color: #ea580c;
            border: 1px solid #fed7aa;
        }

        .priority-medium {
            background: #fefce8;
            color: #ca8a04;
            border: 1px solid #fde68a;
        }

        .priority-low {
            background: #f0fdf4;
            color: #059669;
            border: 1px solid #bbf7d0;
        }

        .issue-description {
            color: #374151;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .required-action {
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .required-action h4 {
            margin: 0 0 8px 0;
            color: #1e40af;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .required-action p {
            margin: 0;
            color: #1e40af;
            font-weight: 500;
        }

        .action-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #6b7280;
        }

        .detail-icon {
            width: 16px;
            height: 16px;
            opacity: 0.6;
        }

        .due-date {
            color: #dc2626;
            font-weight: 600;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            color: #6b7280;
            font-size: 14px;
        }

        .cta-button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
            transition: background-color 0.2s;
        }

        .cta-button:hover {
            background: #5a67d8;
        }

        .urgent-warning {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }

        .urgent-warning h3 {
            margin: 0 0 8px 0;
            color: #dc2626;
            font-size: 16px;
        }

        .urgent-warning p {
            margin: 0;
            color: #dc2626;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>📋 是正措置サマリー / Corrective Actions Summary</h1>
        <p>{{ $employee->name }}様、あなたには{{ $totalActions }}件の是正措置が必要です</p>
        <p>Hello {{ $employee->name }}, you have {{ $totalActions }} corrective action(s) that require your attention</p>
    </div>

    <div class="summary-card">
        <div class="summary-stats">
            <div class="stat-item">
                <span class="stat-number">{{ $totalActions }}</span>
                <span class="stat-label">合計 / Total Actions</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">{{ $correctiveActions->where('priority', 'critical')->count() }}</span>
                <span class="stat-label">緊急 / Critical</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">{{ $correctiveActions->where('priority', 'high')->count() }}</span>
                <span class="stat-label">高優先度 / High Priority</span>
            </div>
        </div>

        <p style="text-align: center; color: #6b7280; margin: 0;">
            監査計画 / Audit Plan: <strong>{{ $auditPlan->name }}</strong><br>
            期限 / Due Date: <strong>{{ \Carbon\Carbon::parse($auditPlan->due_date)->format('Y年n月j日 / M d, Y') }}</strong>
        </p>
    </div>

    @if($correctiveActions->where('priority', 'critical')->count() > 0)
    <div class="urgent-warning">
        <h3>🚨 緊急対応が必要 / URGENT ATTENTION REQUIRED</h3>
        <p>{{ $correctiveActions->where('priority', 'critical')->count() }}件の緊急優先度のアクションが即座の対応を必要としています！</p>
        <p>You have {{ $correctiveActions->where('priority', 'critical')->count() }} critical priority action(s) that need immediate attention!</p>
    </div>
    @endif

    <div class="actions-list">
        <h2 style="margin-top: 0; color: #1f2937; font-size: 20px;">解決が必要なアクション項目 / Action Items Requiring Resolution</h2>

        @foreach($correctiveActions as $action)
        @php
        $asset = $action->auditAsset->asset;
        $dueDate = \Carbon\Carbon::parse($action->due_date);
        $isOverdue = $dueDate->isPast() && $action->status !== 'completed';
        $daysRemaining = $dueDate->diffInDays(now(), false);
        @endphp

        <div class="action-item">
            <div class="action-header">
                <div class="asset-info">
                    資産 / Asset: {{ $asset->asset_id }} ({{ $asset->model }})
                </div>
                <span class="priority-badge priority-{{ $action->priority }}">
                    {{ ucfirst($action->priority) }}
                </span>
            </div>

            <div class="issue-description">
                <strong>問題 / Issue:</strong> {{ $action->issue }}
            </div>

            <div class="required-action">
                <h4>必要なアクション / Required Action</h4>
                <p>{{ $action->action }}</p>
            </div>

            <div class="action-details">
                <div class="detail-item">
                    <span class="detail-icon">📍</span>
                    <span>場所 / Location: {{ $asset->location }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-icon">📅</span>
                    <span class="{{ $isOverdue ? 'due-date' : '' }}">
                        期限 / Due: {{ $dueDate->format('Y年n月j日 / M d, Y') }}
                        @if($isOverdue)
                        ({{ abs($daysRemaining) }}日遅れ / {{ abs($daysRemaining) }} day(s) overdue)
                        @elseif($dueDate->diffInDays(now()) <= 3)
                            (残り{{ $dueDate->diffInDays(now()) }}日 / {{ $dueDate->diffInDays(now()) }} day(s) remaining)
                            @endif
                            </span>
                </div>
                <div class="detail-item">
                    <span class="detail-icon">📝</span>
                    <span>ステータス / Status: {{ ucfirst(str_replace('_', ' ', $action->status)) }}</span>
                </div>
                @if($action->notes)
                <div class="detail-item">
                    <span class="detail-icon">💬</span>
                    <span>メモ / Notes: {{ $action->notes }}</span>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <div class="footer">
        <p>これらの是正措置をできるだけ早く確認し、解決してください。</p>
        <p>ご質問がある場合は、上司または監査チームにお問い合わせください。</p>
        <p>Please review and resolve these corrective actions as soon as possible.</p>
        <p>If you have any questions, please contact your supervisor or the audit team.</p>

        <a href="#" class="cta-button">システムで詳細を確認 / View Details in System</a>

        <p style="margin-top: 20px; font-size: 12px; color: #9ca3af;">
            これは監査管理システムからの自動通知です。<br>
            This is an automated notification from the Audit Management System.<br>
            Generated on {{ now()->format('Y年n月j日 G時i分 / M d, Y \a\t g:i A') }}
        </p>
    </div>
</body>

</html>