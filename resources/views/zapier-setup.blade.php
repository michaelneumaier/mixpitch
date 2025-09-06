<!DOCTYPE html>
<html>
<head>
    <title>Zapier Integration Setup - MixPitch</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold mb-6">MixPitch Zapier Integration</h1>
        
        <div id="api-key-section">
            <h2 class="text-lg font-semibold mb-4">Step 1: Generate Your API Key</h2>
            <p class="text-gray-600 mb-4">Click the button below to generate your Zapier API key:</p>
            
            @if(request()->has('generated'))
                <div class="bg-green-50 border border-green-200 rounded p-4 mb-4">
                    <h3 class="font-semibold text-green-800 mb-2">Your API Key:</h3>
                    <div class="bg-white p-3 rounded border">
                        <code class="text-sm break-all">{{ session('api_key') }}</code>
                    </div>
                    <p class="text-sm text-green-600 mt-2">⚠️ Copy this key now - you won't be able to see it again!</p>
                </div>
            @else
                <form method="POST" action="{{ route('zapier.generate-key') }}">
                    @csrf
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Generate API Key
                    </button>
                </form>
            @endif
        </div>

        <div class="mt-8">
            <h2 class="text-lg font-semibold mb-4">Step 2: Test Your Connection</h2>
            <p class="text-gray-600 mb-4">Use this endpoint to test your API key:</p>
            <div class="bg-gray-100 p-3 rounded border">
                <code class="text-sm">GET {{ url('/api/zapier/auth/test') }}</code><br>
                <code class="text-sm">Authorization: Bearer YOUR_API_KEY</code>
            </div>
        </div>

        <div class="mt-8">
            <h2 class="text-lg font-semibold mb-4">Step 3: Available Endpoints</h2>
            <div class="space-y-2 text-sm">
                <div class="bg-green-50 p-2 rounded">
                    <strong>New Client Trigger:</strong> <code>GET /api/zapier/triggers/clients/new</code>
                </div>
                <div class="bg-blue-50 p-2 rounded">
                    <strong>Create Client:</strong> <code>POST /api/zapier/actions/clients/create</code>
                </div>
            </div>
        </div>

        <div class="mt-8 p-4 bg-yellow-50 rounded border border-yellow-200">
            <h3 class="font-semibold text-yellow-800 mb-2">Next Steps:</h3>
            <ol class="list-decimal list-inside text-sm text-yellow-700 space-y-1">
                <li>Copy your API key above</li>
                <li>Go to your Zapier Developer Dashboard</li>
                <li>Set up authentication using the API key</li>
                <li>Test the connection</li>
                <li>Create your triggers and actions</li>
            </ol>
        </div>
    </div>

</body>
</html>