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
        Schema::table('email_tests', function (Blueprint $table) {
            // Check if the columns don't exist before adding them
            if (! Schema::hasColumn('email_tests', 'recipient_email')) {
                $table->string('recipient_email')->nullable();
            }

            if (! Schema::hasColumn('email_tests', 'subject')) {
                $table->string('subject')->nullable();
            }

            if (! Schema::hasColumn('email_tests', 'template')) {
                $table->string('template')->default('emails.test');
            }

            if (! Schema::hasColumn('email_tests', 'content_variables')) {
                $table->json('content_variables')->nullable();
            }

            if (! Schema::hasColumn('email_tests', 'status')) {
                $table->string('status')->default('pending');
            }

            if (! Schema::hasColumn('email_tests', 'result')) {
                $table->json('result')->nullable();
            }

            if (! Schema::hasColumn('email_tests', 'sent_at')) {
                $table->timestamp('sent_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = [
            'recipient_email',
            'subject',
            'template',
            'content_variables',
            'status',
            'result',
            'sent_at',
        ];

        // Drop columns one by one in separate Schema::table blocks for SQLite compatibility
        foreach ($columns as $column) {
            if (Schema::hasColumn('email_tests', $column)) {
                Schema::table('email_tests', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
