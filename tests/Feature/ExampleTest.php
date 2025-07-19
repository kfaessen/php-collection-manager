<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Test a route that doesn't depend on setup status
        $response = $this->get('/setup');

        // Should return 302 (redirect to setup welcome page)
        $response->assertStatus(302);
    }

    /**
     * Test that the application is accessible.
     */
    public function test_the_application_is_accessible(): void
    {
        $response = $this->get('/');

        // Should return 302 (redirect to setup) or 200 (if setup is complete)
        $response->assertStatus(302);
    }
}
