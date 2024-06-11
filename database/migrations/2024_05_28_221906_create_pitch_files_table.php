<?php

// database/migrations/xxxx_xx_xx_create_pitch_files_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePitchFilesTable extends Migration
{
    public function up()
    {
        Schema::create('pitch_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pitch_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pitch_files');
    }
}
