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
        Schema::table('pitch_file_comments', function (Blueprint $table) {
            if (! Schema::hasColumn('pitch_file_comments', 'client_email')) {
                $table->string('client_email')->nullable()->after('user_id');
            }

            if (! Schema::hasColumn('pitch_file_comments', 'is_client_comment')) {
                $isClientComment = $table->boolean('is_client_comment')->default(false);

                if (Schema::hasColumn('pitch_file_comments', 'is_resolved')) {
                    $isClientComment->after('is_resolved');
                }
            }

            if (Schema::hasColumns('pitch_file_comments', ['pitch_file_id', 'is_client_comment'])) {
                $table->index(['pitch_file_id', 'is_client_comment']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitch_file_comments', function (Blueprint $table) {
            $table->dropIndex(['pitch_file_id', 'is_client_comment']);
            $table->dropColumn(['client_email', 'is_client_comment']);
        });
    }
};
