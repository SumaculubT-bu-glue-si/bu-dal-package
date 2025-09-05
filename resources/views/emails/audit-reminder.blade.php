<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>監査リマインダー / Audit Reminder</title>
    <style>
        body {
            font-family: 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }

        .header h1 {
            color: #dc2626;
            margin: 0;
            font-size: 24px;
        }

        .content {
            margin-bottom: 30px;
        }

        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
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
            font-size: 16px;
            font-weight: bold;
            margin: 0;
        }

        .reminder-box {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }

        .reminder-box h3 {
            color: #92400e;
            margin: 0 0 15px 0;
            font-size: 18px;
        }

        .pending-assets {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }

        .pending-assets h3 {
            color: #1e40af;
            margin: 0 0 15px 0;
            font-size: 18px;
        }

        .pending-assets ul {
            margin: 10px 0;
            padding-left: 20px;
        }

        .pending-assets li {
            margin-bottom: 8px;
            color: #374151;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #6b7280;
            font-size: 14px;
        }

        .progress-info {
            background-color: #dbeafe;
            border: 1px solid #3b82f6;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }

        .progress-info h3 {
            color: #1e40af;
            margin: 0 0 10px 0;
            font-size: 16px;
        }

        .days-remaining {
            font-weight: bold;
        }

        .days-remaining.urgent {
            color: #dc2626;
        }

        .days-remaining.normal {
            color: #059669;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>⏰ 監査リマインダー / Audit Reminder</h1>
        </div>

        <div class="content">
            <!-- Japanese Version -->
            <div class="greeting">
                <strong>{{ $employee->name }}</strong>様
            </div>

            @if($daysRemaining <= 1)
                <div class="urgent-warning">
                <h2>🚨 緊急リマインダー</h2>
                <p>あなたの監査は{{ $daysRemaining == 1 ? '明日' : '今日' }}が期限です！</p>
        </div>
        @else
        <div class="reminder-box">
            <h3>📅 監査期限が近づいています</h3>
            <p>あなたの監査計画 <strong>{{ $auditPlan->name }}</strong> の期限まで<strong>{{ $daysRemaining }}日</strong>です。</p>
        </div>
        @endif

        <div class="progress-info">
            <h3>📊 現在のステータス</h3>
            <p><strong>監査計画：</strong> {{ $auditPlan->name }}</p>
            <p><strong>期限：</strong> {{ \Carbon\Carbon::parse($auditPlan->due_date)->format('Y年n月j日') }}</p>
            <p><strong>残り日数：</strong> <span class="days-remaining {{ $daysRemaining <= 3 ? 'urgent' : 'normal' }}">{{ $daysRemaining }}日</span></p>
        </div>

        <div class="pending-assets">
            <h3>📋 監査未完了の資産</h3>
            <p>まだ<strong>{{ count($pendingAssets) }}</strong>件の資産の監査が必要です。</p>

            @if(count($pendingAssets) > 0)
            <ul>
                @foreach($pendingAssets->take(5) as $asset)
                <li><strong>{{ $asset->model }}</strong> (ID: {{ $asset->asset_id }}) - 場所: {{ $asset->location }}</li>
                @endforeach
                @if(count($pendingAssets) > 5)
                <li>... その他{{ count($pendingAssets) - 5 }}件の資産</li>
                @endif
            </ul>
            @else
            <p>🎉 おめでとうございます！割り当てられたすべての資産の監査が完了しています。</p>
            @endif
        </div>

        <p><strong>必要なアクション：</strong> 期限に間に合うように、残りの監査をできるだけ早く完了してください。</p>

        @if($daysRemaining <= 3)
            <p style="color: #dc2626; font-weight: bold;">⚠️ この監査は期限が近づいています。すぐにこれらのタスクを優先して完了してください。</p>
            @endif

            <p>ご質問やサポートが必要な場合は、すぐに上司またはIT部門にお問い合わせください。</p>

            <hr style="margin: 40px 0; border: 1px solid #e9ecef;">

            <!-- English Version -->
            <div class="greeting">
                Hello <strong>{{ $employee->name }}</strong>,
            </div>

            @if($daysRemaining <= 1)
                <div class="urgent-warning">
                <h2>🚨 URGENT REMINDER</h2>
                <p>Your audit is due {{ $daysRemaining == 1 ? 'TOMORROW' : 'TODAY' }}!</p>
    </div>
    @else
    <div class="reminder-box">
        <h3>📅 Audit Due Date Approaching</h3>
        <p>Your audit plan <strong>{{ $auditPlan->name }}</strong> is due in <strong>{{ $daysRemaining }} days</strong>.</p>
    </div>
    @endif

    <div class="progress-info">
        <h3>📊 Current Status</h3>
        <p><strong>Audit Plan:</strong> {{ $auditPlan->name }}</p>
        <p><strong>Due Date:</strong> {{ \Carbon\Carbon::parse($auditPlan->due_date)->format('F j, Y') }}</p>
        <p><strong>Days Remaining:</strong> <span class="days-remaining {{ $daysRemaining <= 3 ? 'urgent' : 'normal' }}">{{ $daysRemaining }}</span></p>
    </div>

    <div class="pending-assets">
        <h3>📋 Pending Assets to Audit</h3>
        <p>You still have <strong>{{ count($pendingAssets) }}</strong> assets that need to be audited.</p>

        @if(count($pendingAssets) > 0)
        <ul>
            @foreach($pendingAssets->take(5) as $asset)
            <li><strong>{{ $asset->model }}</strong> (ID: {{ $asset->asset_id }}) - Location: {{ $asset->location }}</li>
            @endforeach
            @if(count($pendingAssets) > 5)
            <li>... and {{ count($pendingAssets) - 5 }} more assets</li>
            @endif
        </ul>
        @else
        <p>🎉 Great news! All your assigned assets have been audited.</p>
        @endif
    </div>

    <p><strong>Action Required:</strong> Please complete the remaining audits as soon as possible to meet the deadline.</p>

    @if($daysRemaining <= 3)
        <p style="color: #dc2626; font-weight: bold;">⚠️ This audit is approaching its deadline. Please prioritize completing these tasks immediately.</p>
        @endif

        <p>If you have any questions or need assistance, please contact your supervisor or the IT department right away.</p>
        </div>

        <div class="footer">
            <p>これは資産管理システムからの自動リマインダーです。</p>
            <p>コンプライアンスを確保するため、監査の割り当てを完了してください。</p>
            <hr style="margin: 20px 0; border: 1px solid #e9ecef;">
            <p>This is an automated reminder from the Asset Management System.</p>
            <p>Please complete your audit assignments to ensure compliance.</p>
        </div>
        </div>
</body>

</html>