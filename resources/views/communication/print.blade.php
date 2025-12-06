<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communication Transcript - {{ $project->name }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #1a1a1a;
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }

        @media print {
            body {
                padding: 20px;
            }
            .no-print {
                display: none !important;
            }
        }

        .header {
            border-bottom: 2px solid #7c3aed;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 24pt;
            color: #7c3aed;
            margin-bottom: 8px;
        }

        .header .subtitle {
            font-size: 14pt;
            color: #666;
        }

        .meta-section {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .meta-group h3 {
            font-size: 10pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
            margin-bottom: 8px;
        }

        .meta-group p {
            font-size: 11pt;
            color: #333;
        }

        .messages-section h2 {
            font-size: 16pt;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .message {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            page-break-inside: avoid;
        }

        .message.producer {
            background: #f3e8ff;
            border-left: 4px solid #7c3aed;
        }

        .message.client {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .message-sender {
            font-weight: 600;
            font-size: 11pt;
        }

        .message.producer .message-sender {
            color: #7c3aed;
        }

        .message.client .message-sender {
            color: #3b82f6;
        }

        .message-time {
            font-size: 10pt;
            color: #666;
        }

        .message-content {
            font-size: 11pt;
            color: #333;
            white-space: pre-wrap;
        }

        .urgent-badge {
            display: inline-block;
            background: #fef2f2;
            color: #dc2626;
            font-size: 9pt;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 4px;
            margin-left: 8px;
        }

        .read-status {
            font-size: 9pt;
            color: #666;
            margin-top: 8px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 10pt;
            color: #666;
            text-align: center;
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #7c3aed;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .print-button:hover {
            background: #6d28d9;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">
        Print / Save as PDF
    </button>

    <div class="header">
        <h1>Communication Transcript</h1>
        <p class="subtitle">{{ $project->name }}</p>
    </div>

    <div class="meta-section">
        <div class="meta-group">
            <h3>Project Details</h3>
            <p><strong>Client:</strong> {{ $project->client_name ?? 'Not specified' }}</p>
            <p><strong>Client Email:</strong> {{ $project->client_email ?? 'Not specified' }}</p>
        </div>
        <div class="meta-group">
            <h3>Producer Details</h3>
            <p><strong>Name:</strong> {{ $producer->name ?? 'Unknown Producer' }}</p>
            <p><strong>Email:</strong> {{ $producer->email ?? 'Not specified' }}</p>
        </div>
        <div class="meta-group">
            <h3>Export Information</h3>
            <p><strong>Generated:</strong> {{ $exportDate->format('F j, Y \a\t g:i A') }}</p>
            <p><strong>Total Messages:</strong> {{ $messages->count() }}</p>
        </div>
        <div class="meta-group">
            <h3>Project Status</h3>
            <p><strong>Pitch Status:</strong> {{ ucwords(str_replace('_', ' ', $pitch->status)) }}</p>
        </div>
    </div>

    <div class="messages-section">
        <h2>Messages</h2>

        @forelse($messages as $message)
            @php
                $isProducer = $message->event_type === \App\Models\PitchEvent::TYPE_PRODUCER_MESSAGE;
                $senderName = $isProducer
                    ? ($message->user?->name ?? 'Producer')
                    : ($message->metadata['client_name'] ?? 'Client');
            @endphp

            <div class="message {{ $isProducer ? 'producer' : 'client' }}">
                <div class="message-header">
                    <span class="message-sender">
                        {{ $senderName }}
                        @if($message->is_urgent)
                            <span class="urgent-badge">URGENT</span>
                        @endif
                    </span>
                    <span class="message-time">{{ $message->created_at->format('M j, Y \a\t g:i A') }}</span>
                </div>
                <div class="message-content">{{ $message->comment }}</div>
                @if($message->read_at)
                    <div class="read-status">
                        Read on {{ $message->read_at->format('M j, Y \a\t g:i A') }}
                    </div>
                @endif
            </div>
        @empty
            <div class="empty-state">
                <p>No messages in this conversation yet.</p>
            </div>
        @endforelse
    </div>

    <div class="footer">
        <p>This transcript was generated by MixPitch on {{ $exportDate->format('F j, Y') }}.</p>
        <p>For questions, please contact support@mixpitch.com</p>
    </div>
</body>
</html>
