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
            ->assertSee('data-admin-only hidden', false)
            ->assertSee('id="product-list"', false);
    }

    public function test_profile_and_change_password_have_separate_pages(): void
    {
        $this->get('/profile')
            ->assertOk()
            ->assertSee('/change-password', false)
            ->assertSee(route('profile.edit'), false)
            ->assertDontSee('id="profile-update-form"', false)
            ->assertDontSee('id="change-password-form"', false);

        $this->get('/profile/edit')
            ->assertOk()
            ->assertSee('data-profile-edit-page', false)
            ->assertSee('id="profile-update-form"', false);

        $this->get('/change-password')
            ->assertOk()
            ->assertSee('data-change-password-page', false)
            ->assertSee('id="change-password-form"', false);
    }

    public function test_password_recovery_pages_are_linked_to_the_email_reset_route(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee(route('password.request'), false);

        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('data-forgot-password', false)
            ->assertSee('id="recovery-email"', false);

        $this->get(route('password.reset', ['token' => 'example-reset-token']))
            ->assertOk()
            ->assertSee('data-reset-password', false)
            ->assertSee('value="example-reset-token"', false)
            ->assertDontSee('name="email"', false)
            ->assertDontSee('id="reset-email"', false);
    }
}
