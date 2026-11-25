<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests must not depend on compiled frontend assets (public/build is not committed).
        // The real Vite build is verified separately when the Docker image is built.
        $this->withoutVite();
    }
}
