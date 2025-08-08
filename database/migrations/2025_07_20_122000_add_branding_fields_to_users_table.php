<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'brand_logo_url')) {
                $table->string('brand_logo_url')->nullable()->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'brand_primary')) {
                $table->string('brand_primary', 20)->nullable()->after('brand_logo_url');
            }
            if (!Schema::hasColumn('users', 'brand_secondary')) {
                $table->string('brand_secondary', 20)->nullable()->after('brand_primary');
            }
            if (!Schema::hasColumn('users', 'brand_text')) {
                $table->string('brand_text', 20)->nullable()->after('brand_secondary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'brand_logo_url')) {
                $table->dropColumn('brand_logo_url');
            }
            if (Schema::hasColumn('users', 'brand_primary')) {
                $table->dropColumn('brand_primary');
            }
            if (Schema::hasColumn('users', 'brand_secondary')) {
                $table->dropColumn('brand_secondary');
            }
            if (Schema::hasColumn('users', 'brand_text')) {
                $table->dropColumn('brand_text');
            }
        });
    }
};


