<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>監査アクセスリンク / Your Audit Access Link</title>
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

    .access-button {
        display: inline-block;
        background-color: #2563eb;
        color: #ffffff;
        padding: 15px 30px;
        text-decoration: none;
        border-radius: 6px;
        font-weight: bold;
        margin: 20px 0;
        text-align: center;
    }

    .access-button:hover {
        background-color: #1d4ed8;
    }

    .warning {
        background-color: #fef3c7;
        border: 1px solid #f59e0b;
        border-radius: 6px;
        padding: 15px;
        margin: 20px 0;
    }

    .warning h3 {
        color: #92400e;
        margin: 0 0 10px 0;
        font-size: 16px;
    }

    .footer {
        text-align: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e9ecef;
        color: #6b7280;
        font-size: 14px;
    }

    .expiry-info {
        background-color: #f0f9ff;
        border: 1px solid #0ea5e9;
        border-radius: 6px;
        padding: 15px;
        margin: 20px 0;
    }

    .expiry-info h3 {
        color: #0369a1;
        margin: 0 0 10px 0;
        font-size: 16px;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🔍 資産監査アクセス / Asset Audit Access</h1>
        </div>

        <div class="content">
            <!-- Japanese Version -->
            <div class="greeting">
                @if($employeeName)
                {{ $employeeName }}様
                @else
                ご担当者様
                @endif
            </div>

            <p>監査対象の資産が割り当てられました。下記のボタンをクリックして監査ダッシュボードにアクセスし、割り当てられた資産の確認を開始してください。</p>

            <div style="text-align: center;">
                <a href="{{ $accessUrl }}" class="access-button">
                    監査ダッシュボードにアクセス
                </a>
            </div>

            <div class="expiry-info">
                <h3>⏰ アクセス期限</h3>
                <p>このアクセスリンクは
                    <strong>{{ \Carbon\Carbon::parse($expiresAt)->format('Y年n月j日 G時i分') }}</strong>に期限切れとなります。
                </p>
            </div>

            <div class="warning">
                <h3>⚠️ 重要なセキュリティ通知</h3>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>このリンクはあなた専用です - 他の人と共有しないでください</li>
                    <li>リンクは15分後に自動的に期限切れとなります</li>
                    <li>アクセス時間の延長が必要な場合は、上司にご連絡ください</li>
                </ul>
            </div>

            <p>監査の割り当てについてご質問がある場合や問題が発生した場合は、上司またはIT部門にお問い合わせください。</p>

            <hr style="margin: 40px 0; border: 1px solid #e9ecef;">

            <!-- English Version -->
            <div class="greeting">
                @if($employeeName)
                Hello {{ $employeeName }},
                @else
                Hello,
                @endif
            </div>

            <p>You have been assigned assets for auditing. Click the button below to access your audit dashboard and
                begin reviewing your assigned assets.</p>

            <div style="text-align: center;">
                <a href="{{ $accessUrl }}" class="access-button">
                    Access Audit Dashboard
                </a>
            </div>

            <div class="expiry-info">
                <h3>⏰ Access Expires</h3>
                <p>This access link will expire on
                    <strong>{{ \Carbon\Carbon::parse($expiresAt)->format('F j, Y \a\t g:i A') }}</strong>.
                </p>
            </div>

            <div class="warning">
                <h3>⚠️ Important Security Notice</h3>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>This link is for your use only - do not share it with others</li>
                    <li>The link will automatically expire after 15 minutes</li>
                    <li>If you need extended access, please contact your supervisor</li>
                </ul>
            </div>

            <p>If you have any questions about your audit assignments or encounter any issues, please contact your
                supervisor or the IT department.</p>
        </div>

        <div class="footer">
            <p>これは資産管理システムからの自動メッセージです。</p>
            <p>このアクセスを要求していない場合は、すぐに上司にご連絡ください。</p>
            <hr style="margin: 20px 0; border: 1px solid #e9ecef;">
            <p>This is an automated message from the Asset Management System.</p>
            <p>If you did not request this access, please contact your supervisor immediately.</p>
        </div>
    </div>
</body>

</html>