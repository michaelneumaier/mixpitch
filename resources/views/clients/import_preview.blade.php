<x-layouts.app-sidebar>
    <div class="container mx-auto max-w-4xl py-8">
        <h1 class="text-2xl font-bold mb-4">Preview & Map Columns</h1>

        <form action="{{ route('clients.import.confirm', $job) }}" method="post" class="bg-white border border-gray-200 rounded p-4 mb-6">
            @csrf
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Map your CSV columns to fields</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @foreach(['email','name','company','phone','timezone','tags'] as $field)
                        <div>
                            <div class="text-sm font-medium text-gray-700 mb-1">{{ ucfirst($field) }}</div>
                            <select name="mapping[{{ $field }}]" class="w-full border border-gray-300 rounded p-2">
                                <option value="">-- Not Mapped --</option>
                                @foreach($headers as $h)
                                    <option value="{{ $h }}" {{ ($suggested[$field] ?? null) === $h ? 'selected' : '' }}>{{ $h }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Confirm & Import</button>
        </form>

        <h2 class="text-xl font-semibold mb-2">Sample Rows</h2>
        <div class="bg-white border border-gray-200 rounded overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left">
                        @foreach($headers as $h)
                            <th class="px-3 py-2">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr class="border-t">
                            @foreach($row as $cell)
                                <td class="px-3 py-2">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app-sidebar>


