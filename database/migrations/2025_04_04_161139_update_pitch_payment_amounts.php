<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Pitch;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if we're using SQLite (for local dev/testing) or MySQL (production)
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        
        if ($driver === 'sqlite') {
            // For SQLite, we need to use a different approach
            $paidPitches = DB::table('pitches as p')
                ->join('projects as pr', 'p.project_id', '=', 'pr.id')
                ->where('p.payment_status', Pitch::PAYMENT_STATUS_PAID)
                ->whereRaw('(p.payment_amount IS NULL OR p.payment_amount = 0)')
                ->select('p.id', 'pr.budget')
                ->get();
                
            foreach ($paidPitches as $pitch) {
                DB::table('pitches')
                    ->where('id', $pitch->id)
                    ->update(['payment_amount' => $pitch->budget]);
            }
            
            $updatedCount = $paidPitches->count();
        } else {
            // For MySQL/PostgreSQL, we can use the more efficient JOIN in UPDATE
            DB::statement('
                UPDATE pitches p
                JOIN projects pr ON p.project_id = pr.id
                SET p.payment_amount = pr.budget
                WHERE p.payment_status = ?
                AND (p.payment_amount IS NULL OR p.payment_amount = 0)
            ', [Pitch::PAYMENT_STATUS_PAID]);
            
            // Count how many records were updated
            $updatedCount = DB::table('pitches')
                ->where('payment_status', Pitch::PAYMENT_STATUS_PAID)
                ->whereRaw('payment_amount > 0')
                ->count();
        }
            
        \Illuminate\Support\Facades\Log::info("Migration: Updated {$updatedCount} paid pitches with missing payment amounts");
    }

    /**
     * Reverse the migrations.
     * 
     * No action taken for down - we don't want to clear payment amounts
     */
    public function down(): void
    {
        // No need to reverse this data update
    }
};
