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
        Schema::table('email_audits', function (Blueprint $table) {
            $table->longText('content')->nullable()->after('metadata');
            $table->json('headers')->nullable()->after('metadata');
            $table->string('message_id')->nullable()->after('subject');
            $table->string('recipient_name')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_audits', function (Blueprint $table) {
            $table->dropColumn('content');
            $table->dropColumn('headers');
            $table->dropColumn('message_id');
            $table->dropColumn('recipient_name');
        });
    }
};
