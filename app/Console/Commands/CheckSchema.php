<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckSchema extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:check-schema {table?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check database schema for tables related to ratings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $table = $this->argument('table');
        
        if ($table) {
            $this->checkTable($table);
            return 0;
        }
        
        // Check relevant tables for rating functionality
        $this->checkTable('pitch_events');
        $this->checkTable('pitches');
        $this->checkTable('users');
        
        return 0;
    }
    
    protected function checkTable($tableName)
    {
        $this->info("Checking schema for table: {$tableName}");
        
        if (!Schema::hasTable($tableName)) {
            $this->error("Table {$tableName} does not exist!");
            return;
        }
        
        // Get column information using Schema
        $columns = Schema::getColumns($tableName);
        
        $this->info("Columns in {$tableName}:");
        $headers = ['Column', 'Type', 'Nullable', 'Default'];
        $rows = [];
        
        foreach ($columns as $column) {
            $rows[] = [
                $column['name'],
                $column['type'],
                $column['nullable'] ? 'YES' : 'NO',
                $column['default'] ?? 'NULL',
            ];
        }
        
        $this->table($headers, $rows);
        
        // Check specific columns relevant to ratings
        if ($tableName === 'pitch_events') {
            if (!Schema::hasColumn($tableName, 'rating')) {
                $this->error("Table {$tableName} is missing the 'rating' column!");
            }
        }
        
        // Show some sample data
        $this->info("Sample data from {$tableName}:");
        $sampleData = DB::table($tableName)->limit(5)->get();
        
        if ($sampleData->isEmpty()) {
            $this->warn("No data found in {$tableName}");
            return;
        }
        
        $headers = array_keys((array) $sampleData[0]);
        $rows = [];
        
        foreach ($sampleData as $data) {
            $row = [];
            foreach ($headers as $header) {
                $value = $data->$header ?? null;
                
                // Format JSON or arrays for display
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                
                // Truncate long values
                if (is_string($value) && strlen($value) > 30) {
                    $value = substr($value, 0, 27) . '...';
                }
                
                $row[] = $value === null ? 'NULL' : $value;
            }
            $rows[] = $row;
        }
        
        $this->table($headers, $rows);
        
        // If this is the pitch_events table, check for ratings that are set
        if ($tableName === 'pitch_events') {
            $this->info("Checking for ratings in pitch_events:");
            $ratings = DB::table('pitch_events')
                ->whereNotNull('rating')
                ->get();
                
            if ($ratings->isEmpty()) {
                $this->warn("No ratings found in pitch_events table!");
            } else {
                $this->info("Found " . count($ratings) . " ratings in pitch_events table");
                
                $headers = ['id', 'pitch_id', 'event_type', 'status', 'rating', 'created_by', 'created_at'];
                $rows = [];
                
                foreach ($ratings as $rating) {
                    $rows[] = [
                        $rating->id,
                        $rating->pitch_id,
                        $rating->event_type,
                        $rating->status,
                        $rating->rating,
                        $rating->created_by,
                        $rating->created_at,
                    ];
                }
                
                $this->table($headers, $rows);
            }
        }
        
        $this->newLine();
    }
}
