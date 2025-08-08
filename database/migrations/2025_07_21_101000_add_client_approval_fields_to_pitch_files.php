<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pitch_files', function (Blueprint $table) {
            if (!Schema::hasColumn('pitch_files', 'client_approval_status')) {
                $table->string('client_approval_status')->nullable()->after('note'); // pending, approved, rejected
            }
            if (!Schema::hasColumn('pitch_files', 'client_approved_at')) {
                $table->timestamp('client_approved_at')->nullable()->after('client_approval_status');
            }
            if (!Schema::hasColumn('pitch_files', 'client_approval_note')) {
                $table->text('client_approval_note')->nullable()->after('client_approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pitch_files', function (Blueprint $table) {
            if (Schema::hasColumn('pitch_files', 'client_approval_status')) {
                $table->dropColumn('client_approval_status');
            }
            if (Schema::hasColumn('pitch_files', 'client_approved_at')) {
                $table->dropColumn('client_approved_at');
            }
            if (Schema::hasColumn('pitch_files', 'client_approval_note')) {
                $table->dropColumn('client_approval_note');
            }
        });
    }
};


