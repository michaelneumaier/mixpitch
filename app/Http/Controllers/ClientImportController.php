<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessClientImport;
use App\Models\ClientImportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientImportController extends Controller
{
    public function index()
    {
        $jobs = ClientImportJob::where('user_id', Auth::id())->latest()->limit(20)->get();

        return view('clients.import', compact('jobs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $userId = Auth::id();
        $path = $request->file('file')->store('client-imports');

        $job = ClientImportJob::create([
            'user_id' => $userId,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'storage_path' => $path,
            'status' => 'uploaded',
        ]);

        return redirect()->route('clients.import.preview', $job)->with('success', 'File uploaded. Review mapping and confirm import.');
    }

    public function preview(ClientImportJob $job)
    {
        $this->authorizeJob($job);

        $filePath = storage_path('app/'.$job->storage_path);
        $spl = new \SplFileObject($filePath);
        $spl->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);

        $headers = [];
        $rows = [];
        $rowIndex = -1;
        foreach ($spl as $row) {
            if ($row === [null] || $row === false) {
                continue;
            }
            $rowIndex++;
            if ($rowIndex === 0) {
                $headers = array_map(fn ($h) => (string) $h, $row);

                continue;
            }
            $rows[] = $row;
            if (count($rows) >= 20) {
                break;
            }
        }

        // Suggest mapping by matching known names
        $targets = ['email', 'name', 'company', 'phone', 'timezone', 'tags'];
        $suggested = [];
        foreach ($targets as $target) {
            $suggested[$target] = null;
            foreach ($headers as $i => $h) {
                if (strtolower(trim($h)) === $target) {
                    $suggested[$target] = $h;
                    break;
                }
            }
        }

        return view('clients.import_preview', [
            'job' => $job,
            'headers' => $headers,
            'rows' => $rows,
            'suggested' => $suggested,
        ]);
    }

    public function confirm(Request $request, ClientImportJob $job)
    {
        $this->authorizeJob($job);
        $headers = $request->input('headers', []);
        // Mapping is an array like mapping[email] = 'Email Address'
        $mapping = $request->input('mapping', []);

        $summary = $job->summary ?? [];
        $summary['mapping'] = $mapping;
        $job->update(['summary' => $summary, 'status' => 'pending']);

        ProcessClientImport::dispatch($job->id);

        return redirect()->route('clients.import.index')->with('success', 'Import started. You will see results here shortly.');
    }

    private function authorizeJob(ClientImportJob $job): void
    {
        abort_unless($job->user_id === Auth::id(), 403);
    }

    public function sample(): StreamedResponse
    {
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="sample-clients.csv"'];
        $rows = [
            ['email', 'name', 'company', 'phone', 'timezone', 'tags'],
            ['client1@example.com', 'Client One', 'Acme Inc', '+1 555-1111', 'UTC', 'vip,new'],
            ['client2@example.com', 'Client Two', 'Beta LLC', '+1 555-2222', 'America/Los_Angeles', 'repeat'],
        ];

        return response()->stream(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 200, $headers);
    }
}
