<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class NonRefreshingTestCase extends BaseTestCase
{
    use CreatesApplication;

    // This test case does NOT use RefreshDatabase to avoid wiping the main database
    // Use this for tests that don't need database isolation or are testing external services
}
