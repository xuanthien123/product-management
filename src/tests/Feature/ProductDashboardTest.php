<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_login_and_dashboard_pages_load_as_api_clients(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('data-login-form', false)
            ->assertSee('Sign in to your workspace');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('data-dashboard', false)
            ->assertSee('id="product-list"', false);
    }
}
