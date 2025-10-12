<?php

namespace Tests\Unit\Models;

use App\Models\Pitch;
use App\Models\PitchFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PitchFileVersionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_parent_relationship()
    {
        $parent = PitchFile::factory()->create(['parent_file_id' => null]);
        $child = PitchFile::factory()->create([
            'parent_file_id' => $parent->id,
            'file_version_number' => 2,
        ]);

        $this->assertEquals($parent->id, $child->parent->id);
    }

    /** @test */
    public function it_has_versions_relationship()
    {
        $parent = PitchFile::factory()->create(['parent_file_id' => null]);
        $v2 = PitchFile::factory()->create([
            'parent_file_id' => $parent->id,
            'file_version_number' => 2,
        ]);
        $v3 = PitchFile::factory()->create([
            'parent_file_id' => $parent->id,
            'file_version_number' => 3,
        ]);

        $this->assertCount(2, $parent->versions);
        $this->assertEquals(2, $parent->versions->first()->file_version_number);
    }

    /** @test */
    public function get_root_file_returns_self_when_no_parent()
    {
        $file = PitchFile::factory()->create(['parent_file_id' => null]);

        $this->assertEquals($file->id, $file->getRootFile()->id);
    }

    /** @test */
    public function get_root_file_returns_parent_when_is_version()
    {
        $parent = PitchFile::factory()->create(['parent_file_id' => null]);
        $child = PitchFile::factory()->create([
            'parent_file_id' => $parent->id,
            'file_version_number' => 2,
        ]);

        $this->assertEquals($parent->id, $child->getRootFile()->id);
    }

    /** @test */
    public function has_multiple_versions_returns_false_for_single_file()
    {
        $file = PitchFile::factory()->create(['parent_file_id' => null]);

        $this->assertFalse($file->hasMultipleVersions());
    }

    /** @test */
    public function has_multiple_versions_returns_true_when_versions_exist()
    {
        $parent = PitchFile::factory()->create(['parent_file_id' => null]);
        PitchFile::factory()->create([
            'parent_file_id' => $parent->id,
            'file_version_number' => 2,
        ]);

        $this->assertTrue($parent->fresh()->hasMultipleVersions());
    }

    /** @test */
    public function has_multiple_versions_returns_true_when_file_is_a_version()
    {
        $parent = PitchFile::factory()->create(['parent_file_id' => null]);
        $child = PitchFile::factory()->create([
            'parent_file_id' => $parent->id,
            'file_version_number' => 2,
        ]);

        $this->assertTrue($child->hasMultipleVersions());
    }

    /** @test */
    public function get_all_versions_with_self_returns_all_versions_sorted()
    {
        $pitch = Pitch::factory()->create();
        $parent = PitchFile::factory()->recycle($pitch)->create([
            'parent_file_id' => null,
            'file_version_number' => 1,
        ]);
        $v2 = PitchFile::factory()->recycle($pitch)->create([
            'parent_file_id' => $parent->id,
            'file_version_number' => 2,
        ]);
        $v3 = PitchFile::factory()->recycle($pitch)->create([
            'parent_file_id' => $parent->id,
            'file_version_number' => 3,
        ]);

        $allVersions = $parent->getAllVersionsWithSelf();

        $this->assertCount(3, $allVersions);
        $this->assertEquals(1, $allVersions->first()->file_version_number);
        $this->assertEquals(3, $allVersions->last()->file_version_number);
    }

    /** @test */
    public function get_version_label_returns_null_for_single_version_file()
    {
        $file = PitchFile::factory()->create(['parent_file_id' => null]);

        $this->assertNull($file->getVersionLabel());
    }

    /** @test */
    public function get_version_label_returns_formatted_label_when_multiple_versions()
    {
        $parent = PitchFile::factory()->create([
            'parent_file_id' => null,
            'file_version_number' => 1,
        ]);
        $v2 = PitchFile::factory()->create([
            'parent_file_id' => $parent->id,
            'file_version_number' => 2,
        ]);

        $this->assertEquals('V1', $parent->fresh()->getVersionLabel());
        $this->assertEquals('V2', $v2->getVersionLabel());
    }

    /** @test */
    public function is_latest_version_returns_true_for_highest_version()
    {
        $parent = PitchFile::factory()->create([
            'parent_file_id' => null,
            'file_version_number' => 1,
        ]);
        $v2 = PitchFile::factory()->create([
            'parent_file_id' => $parent->id,
            'file_version_number' => 2,
        ]);
        $v3 = PitchFile::factory()->create([
            'parent_file_id' => $parent->id,
            'file_version_number' => 3,
        ]);

        $this->assertFalse($parent->fresh()->isLatestVersion());
        $this->assertFalse($v2->fresh()->isLatestVersion());
        $this->assertTrue($v3->fresh()->isLatestVersion());
    }

    /** @test */
    public function latest_versions_scope_returns_only_latest_versions()
    {
        $pitch = Pitch::factory()->create();

        // File family 1
        $parent1 = PitchFile::factory()->recycle($pitch)->create(['parent_file_id' => null]);
        $v1_2 = PitchFile::factory()->recycle($pitch)->create([
            'parent_file_id' => $parent1->id,
            'file_version_number' => 2,
        ]);

        // File family 2
        $parent2 = PitchFile::factory()->recycle($pitch)->create(['parent_file_id' => null]);
        $v2_2 = PitchFile::factory()->recycle($pitch)->create([
            'parent_file_id' => $parent2->id,
            'file_version_number' => 2,
        ]);
        $v2_3 = PitchFile::factory()->recycle($pitch)->create([
            'parent_file_id' => $parent2->id,
            'file_version_number' => 3,
        ]);

        // Single file with no versions
        $single = PitchFile::factory()->recycle($pitch)->create(['parent_file_id' => null]);

        $latest = PitchFile::latestVersions()->get();

        // Should include: v1_2 (latest of family 1), v2_3 (latest of family 2), single
        $this->assertCount(3, $latest);
        $this->assertTrue($latest->contains('id', $v1_2->id));
        $this->assertTrue($latest->contains('id', $v2_3->id));
        $this->assertTrue($latest->contains('id', $single->id));
    }
}
