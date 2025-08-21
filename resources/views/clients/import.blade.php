<x-layouts.app-sidebar>
    <div class="container mx-auto max-w-4xl py-8">
        <h1 class="text-2xl font-bold mb-4">Import Clients (CSV)</h1>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2 rounded mb-4">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('clients.import.store') }}" method="post" enctype="multipart/form-data" class="bg-white border border-gray-200 rounded p-4 mb-6">
            @csrf
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">CSV File</label>
                <input type="file" name="file" accept=".csv,text/csv" class="block w-full border border-gray-300 rounded p-2">
                <p class="text-xs text-gray-500 mt-1">Headers supported: email, name, company, phone, timezone, tags (comma-separated)</p>
            </div>
            <div class="flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Upload & Continue</button>
                <a href="{{ route('clients.import.sample') }}" class="px-4 py-2 bg-gray-100 text-gray-800 rounded border">Download sample CSV</a>
            </div>
        </form>

        <h2 class="text-xl font-semibold mb-2">Recent Imports</h2>
        <div class="bg-white border border-gray-200 rounded">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left">
                        <th class="px-3 py-2">File</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Total</th>
                        <th class="px-3 py-2">Imported</th>
                        <th class="px-3 py-2">Duplicates</th>
                        <th class="px-3 py-2">Errors</th>
                        <th class="px-3 py-2">Error Samples</th>
                        <th class="px-3 py-2">Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                        <tr class="border-t">
                            <td class="px-3 py-2">{{ $job->original_filename }}</td>
                            <td class="px-3 py-2">{{ ucfirst($job->status) }}</td>
                            <td class="px-3 py-2">{{ $job->total_rows }}</td>
                            <td class="px-3 py-2">{{ $job->imported_rows }}</td>
                            <td class="px-3 py-2">{{ $job->duplicate_rows }}</td>
                            <td class="px-3 py-2">{{ $job->error_rows }}</td>
                            <td class="px-3 py-2">
                                @php $errs = $job->summary['errors'] ?? []; @endphp
                                @if(!empty($errs))
                                    <details>
                                        <summary class="cursor-pointer text-gray-700">View</summary>
                                        <ul class="list-disc list-inside text-xs text-gray-600 max-h-32 overflow-auto">
                                            @foreach($errs as $err)
                                                <li>Row {{ $err['row'] ?? '?' }} — {{ $err['message'] ?? 'Error' }}</li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $job->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-4 text-center text-gray-500">No imports yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app-sidebar>


