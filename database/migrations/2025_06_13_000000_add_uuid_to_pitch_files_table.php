<?php

use App\Models\PitchFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pitch_files', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->index('uuid');
        });

        // Generate UUIDs for existing records
        PitchFile::whereNull('uuid')->chunkById(100, function ($files) {
            foreach ($files as $file) {
                $file->uuid = Str::uuid();
                $file->save();
            }
        });

        // For new installations or if there are no existing records, make it non-nullable
        // For existing installations, you may want to run this separately after ensuring all records have UUIDs
        if (PitchFile::whereNull('uuid')->count() === 0) {
            Schema::table('pitch_files', function (Blueprint $table) {
                $table->unique('uuid');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitch_files', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            if (Schema::hasColumn('pitch_files', 'uuid')) {
                $table->dropColumn('uuid');
            }
        });
    }
};
