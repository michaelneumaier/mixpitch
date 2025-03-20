<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Test Email from MixPitch</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #4f46e5;
            margin-bottom: 20px;
        }
        p {
            margin-bottom: 10px;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test Email from MixPitch</h1>
        <p>This is a test email sent from the MixPitch application.</p>
        <p>Your Amazon SES email configuration is working correctly!</p>
        <p>You can now use this configuration to send transactional emails, notifications, and other communications to your users.</p>
        <div class="footer">
            <p>&copy; {{ date('Y') }} MixPitch. All rights reserved.</p>
        </div>
    </div>
</body>
</html> 