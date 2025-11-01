<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('license_signatures', function (Blueprint $table) {
            // Make user_id nullable to support guest client signatures
            $table->foreignId('user_id')->nullable()->change();

            // Add client_email for guest client identification
            $table->string('client_email')->nullable()->after('user_id');

            // Add signed_via to track where signature was created
            $table->enum('signed_via', ['pitch_creation', 'client_portal'])
                ->default('pitch_creation')
                ->after('signature_method');

            // Add indexes for client_email queries
            $table->index(['project_id', 'client_email']);
            $table->index(['client_email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('license_signatures', function (Blueprint $table) {
            // Drop new indexes
            $table->dropIndex(['project_id', 'client_email']);
            $table->dropIndex(['client_email']);

            // Remove new columns
            $table->dropColumn('client_email');
            $table->dropColumn('signed_via');

            // Restore user_id to NOT NULL (careful - this will fail if NULL values exist)
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
