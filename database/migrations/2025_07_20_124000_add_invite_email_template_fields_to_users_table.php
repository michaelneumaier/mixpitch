<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'invite_email_subject')) {
                $table->string('invite_email_subject')->nullable()->after('brand_text');
            }
            if (! Schema::hasColumn('users', 'invite_email_body')) {
                $table->text('invite_email_body')->nullable()->after('invite_email_subject');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'invite_email_subject')) {
                $table->dropColumn('invite_email_subject');
            }
            if (Schema::hasColumn('users', 'invite_email_body')) {
                $table->dropColumn('invite_email_body');
            }
        });
    }
};
