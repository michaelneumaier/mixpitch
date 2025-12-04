<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| Automatically bind TestCase to all tests in Feature and Unit directories.
| TestCase already includes RefreshDatabase trait, so we don't need to apply
| it twice. This ensures all tests use the in-memory SQLite test database.
*/
uses(TestCase::class)->in('Feature', 'Unit');
