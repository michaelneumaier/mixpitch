<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Method Not Allowed</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        h1 {
            color: #e53e3e;
        }
        .message {
            padding: 1rem;
            background-color: #fef2f2;
            border-left: 4px solid #e53e3e;
            margin-bottom: 1.5rem;
        }
        .debug-info {
            background-color: #f3f4f6;
            padding: 1.5rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
        }
        pre {
            background-color: #1f2937;
            color: #f9fafb;
            padding: 1rem;
            border-radius: 0.25rem;
            overflow-x: auto;
        }
        .note {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
        }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 0.25rem;
            margin-top: 1rem;
        }
        .btn:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body>
    <h1>405 Method Not Allowed</h1>
    
    <div class="message">
        <p><strong>Error:</strong> {{ $message }}</p>
    </div>
    
    <div class="debug-info">
        <h2>Debug Information</h2>
        <h3>Request Details</h3>
        <pre>{{ json_encode($debug_info, JSON_PRETTY_PRINT) }}</pre>
        
        <div class="note">
            <p><strong>Note:</strong> {{ $debug_info['note'] }}</p>
        </div>
    </div>
    
    <a href="{{ url()->previous() }}" class="btn">Go Back</a>
</body>
</html> 