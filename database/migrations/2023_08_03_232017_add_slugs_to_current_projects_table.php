<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::table('projects')->get()->each(function ($project) {
            $slug = Str::slug($project->name);
            $count = \DB::table('projects')->where('slug', 'like', "%{$slug}%")->count();
            if ($count > 0) {
                $slug .= '-'.($count + 1);
            }
            \DB::table('projects')
                ->where('id', $project->id)
                ->update(['slug' => $slug]);
        });
    }

    public function down()
    {
        DB::table('projects')->update(['slug' => null]);
    }
};
