<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Import DB facade

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pitches', function (Blueprint $table) {
            // Add completed_at column
            $table->timestamp('completed_at')->nullable();
            
            // Add payment-related columns if they don't exist
            if (!Schema::hasColumn('pitches', 'payment_status')) {
                $table->string('payment_status')->nullable();
            }
            
            if (!Schema::hasColumn('pitches', 'payment_amount')) {
                $table->decimal('payment_amount', 10, 2)->nullable();
            }
            
            if (!Schema::hasColumn('pitches', 'payment_completed_at')) {
                $table->timestamp('payment_completed_at')->nullable();
            }
            
            if (!Schema::hasColumn('pitches', 'final_invoice_id')) {
                $table->string('final_invoice_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = 'pitches';
        $columns = [
            'completed_at',
            'payment_status',
            'payment_amount',
            'payment_completed_at',
            'final_invoice_id'
        ];
        $connection = Schema::getConnection()->getName();

        if ($connection === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=off;');
        }

        foreach ($columns as $column) {
            if (Schema::hasColumn($tableName, $column)) {
                Schema::table($tableName, function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        if ($connection === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=on;');
        }
    }
};
