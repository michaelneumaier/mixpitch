<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    /**
     * Indicates whether migrations should be run for each test.
     *
     * @var bool
     */
    protected $refreshDatabaseUsingMigrations = true;
}
