<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pitches', function (Blueprint $table) {
            if (! Schema::hasColumn('pitches', 'delivery_sort_order')) {
                $column = $table->unsignedInteger('delivery_sort_order')->nullable();

                if (Schema::hasColumn('pitches', 'watermarking_enabled')) {
                    $column->after('watermarking_enabled');
                }

                $table->index('delivery_sort_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pitches', function (Blueprint $table) {
            if (Schema::hasColumn('pitches', 'delivery_sort_order')) {
                $table->dropIndex(['delivery_sort_order']);
                $table->dropColumn('delivery_sort_order');
            }
        });
    }
};
