<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'client_id')) {
                $table->foreignId('client_id')->nullable()->after('client_email')
                    ->constrained('clients')->nullOnDelete();
            }
        });

        // Backfill client_id for existing client management projects
        // Safe to use DB-level queries to avoid loading models
        $chunkSize = 500;
        $lastId = 0;

        do {
            $projects = DB::table('projects')
                ->where('id', '>', $lastId)
                ->whereNull('client_id')
                ->where('workflow_type', 'client_management')
                ->whereNotNull('client_email')
                ->orderBy('id')
                ->limit($chunkSize)
                ->get();

            if ($projects->isEmpty()) {
                break;
            }

            foreach ($projects as $project) {
                $lastId = $project->id;

                try {
                    // Find or create the client for this producer+email pair
                    $client = DB::table('clients')
                        ->where('user_id', $project->user_id)
                        ->where('email', $project->client_email)
                        ->first();

                    if (! $client) {
                        $clientId = DB::table('clients')->insertGetId([
                            'user_id' => $project->user_id,
                            'email' => $project->client_email,
                            'name' => $project->client_name,
                            'company' => null,
                            'phone' => null,
                            'timezone' => 'UTC',
                            'preferences' => null,
                            'notes' => null,
                            'tags' => null,
                            'status' => 'active',
                            'last_contacted_at' => null,
                            'total_spent' => 0,
                            'total_projects' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $clientId = $client->id;
                    }

                    DB::table('projects')
                        ->where('id', $project->id)
                        ->update(['client_id' => $clientId, 'updated_at' => now()]);
                } catch (\Throwable $e) {
                    Log::error('Client backfill failed for project', [
                        'project_id' => $project->id,
                        'user_id' => $project->user_id,
                        'client_email' => $project->client_email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } while (true);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'client_id')) {
                $table->dropConstrainedForeignId('client_id');
            }
        });
    }
};


