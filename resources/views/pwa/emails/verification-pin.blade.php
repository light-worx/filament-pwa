<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body        { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                      background: #f5f6f8; margin: 0; padding: 40px 0; }
        .wrapper    { max-width: 480px; margin: 0 auto; }
        .card       { background: #fff; border-radius: 16px; padding: 40px 36px;
                      box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .app-name   { font-size: 13px; font-weight: 600; color: #6b7280;
                      text-transform: uppercase; letter-spacing: .08em; margin-bottom: 24px; }
        h1          { font-size: 22px; font-weight: 700; color: #111827; margin: 0 0 8px; }
        p           { font-size: 15px; color: #4b5563; line-height: 1.6; margin: 0 0 24px; }
        .pin        { font-size: 42px; font-weight: 800; letter-spacing: .25em;
                      color: #1f2937; text-align: center; background: #f3f4f6;
                      border-radius: 12px; padding: 20px; margin: 0 0 24px; }
        .expiry     { font-size: 13px; color: #9ca3af; text-align: center; }
        .footer     { margin-top: 32px; font-size: 12px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="app-name">{{ $appName }}</div>
        <h1>Verify your email</h1>
        <p>Enter the code below in the app to verify your email address. It expires in 15 minutes.</p>
        <div class="pin">{{ $pin }}</div>
        <p class="expiry">If you didn't request this, you can safely ignore this email.</p>
    </div>
    <div class="footer">{{ $appName }} &mdash; sent to you because this email was entered on a device.</div>
</div>
</body>
</html>