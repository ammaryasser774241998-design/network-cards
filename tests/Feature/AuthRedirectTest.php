<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_opens_direct_sales_by_default(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'login' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('direct_sales.index'));
    }

    public function test_home_page_redirects_to_direct_sales_and_dashboard_remains_available(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertRedirect(route('direct_sales.index'));

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk();
    }
}
