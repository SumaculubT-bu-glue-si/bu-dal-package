<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新しい監査割り当て / New Audit Assignment</title>
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
            color: #2563eb;
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

        .asset-list {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }

        .asset-list h3 {
            color: #1e40af;
            margin: 0 0 15px 0;
            font-size: 18px;
        }

        .asset-list ul {
            margin: 10px 0;
            padding-left: 20px;
        }

        .asset-list li {
            margin-bottom: 8px;
            color: #374151;
        }

        .due-date {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }

        .due-date h3 {
            color: #92400e;
            margin: 0 0 10px 0;
            font-size: 16px;
        }

        .due-date p {
            margin: 8px 0;
            color: #92400e;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #6b7280;
            font-size: 14px;
        }

        .info-box {
            background-color: #dbeafe;
            border: 1px solid #3b82f6;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }

        .info-box h3 {
            color: #1e40af;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🔍 新しい監査割り当て / New Audit Assignment</h1>
        </div>

        <div class="content">
            <!-- Japanese Version -->
            <div class="greeting">
                <strong>{{ $employee->name }}</strong>様
            </div>

            <p>新しい監査計画に参加していただくことになりました：<strong>{{ $auditPlan->name }}</strong></p>

            <div class="info-box">
                <h3>📋 監査計画詳細</h3>
                <p><strong>説明：</strong> {{ $auditPlan->description ?? '説明なし' }}</p>
                <p><strong>ステータス：</strong> {{ $auditPlan->status }}</p>
            </div>

            <div class="asset-list">
                <h3>📋 あなたの監査責任</h3>
                <p>あなたには<strong>{{ count($assignedAssets) }}</strong>件の資産が監査対象として割り当てられています。</p>

                @if(count($assignedAssets) > 0)
                <ul>
                    @foreach($assignedAssets->take(5) as $asset)
                    <li><strong>{{ $asset->model }}</strong> (ID: {{ $asset->asset_id }}) - 場所: {{ $asset->location }}</li>
                    @endforeach
                    @if(count($assignedAssets) > 5)
                    <li>... その他{{ count($assignedAssets) - 5 }}件の資産</li>
                    @endif
                </ul>
                @else
                <p>まだ具体的な資産は割り当てられていません。間もなく資産の割り当てをお知らせします。</p>
                @endif
            </div>

            <div class="due-date">
                <h3>⏰ 重要な日程</h3>
                <p><strong>開始日：</strong> {{ \Carbon\Carbon::parse($auditPlan->start_date)->format('Y年n月j日') }}</p>
                <p><strong>期限：</strong> {{ \Carbon\Carbon::parse($auditPlan->due_date)->format('Y年n月j日') }}</p>
            </div>

            <p>割り当てられた資産を確認し、監査プロセスを開始してください。間もなく監査ダッシュボードへのアクセスをお送りします。</p>

            <p>監査の割り当てについてご質問がある場合は、上司またはIT部門にお問い合わせください。</p>

            <hr style="margin: 40px 0; border: 1px solid #e9ecef;">

            <!-- English Version -->
            <div class="greeting">
                Hello <strong>{{ $employee->name }}</strong>,
            </div>

            <p>You have been assigned to participate in a new audit plan: <strong>{{ $auditPlan->name }}</strong></p>

            <div class="info-box">
                <h3>📋 Audit Plan Details</h3>
                <p><strong>Description:</strong> {{ $auditPlan->description ?? 'No description provided' }}</p>
                <p><strong>Status:</strong> {{ $auditPlan->status }}</p>
            </div>

            <div class="asset-list">
                <h3>📋 Your Audit Responsibilities</h3>
                <p>You have been assigned <strong>{{ count($assignedAssets) }}</strong> assets to audit.</p>

                @if(count($assignedAssets) > 0)
                <ul>
                    @foreach($assignedAssets->take(5) as $asset)
                    <li><strong>{{ $asset->model }}</strong> (ID: {{ $asset->asset_id }}) - Location: {{ $asset->location }}</li>
                    @endforeach
                    @if(count($assignedAssets) > 5)
                    <li>... and {{ count($assignedAssets) - 5 }} more assets</li>
                    @endif
                </ul>
                @else
                <p>No specific assets assigned yet. You will receive asset assignments shortly.</p>
                @endif
            </div>

            <div class="due-date">
                <h3>⏰ Important Dates</h3>
                <p><strong>Start Date:</strong> {{ \Carbon\Carbon::parse($auditPlan->start_date)->format('F j, Y') }}</p>
                <p><strong>Due Date:</strong> {{ \Carbon\Carbon::parse($auditPlan->due_date)->format('F j, Y') }}</p>
            </div>

            <p>Please review your assigned assets and begin the audit process. You will receive access to the audit dashboard shortly.</p>

            <p>If you have any questions about your audit assignment, please contact your supervisor or the IT department.</p>
        </div>

        <div class="footer">
            <p>これは資産管理システムからの自動メッセージです。</p>
            <p>この割り当てに心当たりがない場合は、すぐに上司にご連絡ください。</p>
            <hr style="margin: 20px 0; border: 1px solid #e9ecef;">
            <p>This is an automated message from the Asset Management System.</p>
            <p>If you did not expect this assignment, please contact your supervisor immediately.</p>
        </div>
    </div>
</body>

</html>