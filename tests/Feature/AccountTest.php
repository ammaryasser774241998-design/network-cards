<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_page_shows_signed_in_user_details_without_exposing_password(): void
    {
        $user = User::factory()->create([
            'name' => 'مدير الشبكة',
            'phone_number' => '777123456',
            'email' => 'admin@example.com',
            'password' => 'old-password',
        ]);

        $this->actingAs($user)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee('مدير الشبكة')
            ->assertSee('777123456')
            ->assertSee('admin@example.com')
            ->assertSee('لا يمكن عرض كلمة المرور الحالية')
            ->assertDontSee('old-password')
            ->assertDontSee($user->password);
    }

    public function test_system_pages_include_scroll_position_preservation_for_form_submissions(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee('network-cards-system:scroll-position')
            ->assertSee('window.scrollY')
            ->assertSee('window.scrollTo(0, scrollPosition.top)')
            ->assertSee("form.target !== '_blank'", false);
    }

    public function test_account_page_no_longer_offers_profile_image_upload(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('account.index'))
            ->assertOk()
            ->assertDontSee('صورة الحساب')
            ->assertDontSee('type="file"');

        $this->assertFalse(Route::has('profile.avatar.update'));
    }

    public function test_user_can_change_password_after_confirming_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user)
            ->put(route('account.password.update'), [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('account.index'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_user_cannot_change_password_with_invalid_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user)
            ->from(route('account.index'))
            ->put(route('account.password.update'), [
                'current_password' => 'incorrect-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('account.index'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_user_can_set_the_management_budget_from_account_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('account.budget.update'), [
                'action' => 'set',
                'new_budget' => '12500.50',
            ])
            ->assertRedirect(route('account.index'))
            ->assertSessionHas('success');

        $this->assertEquals(12500.50, (float) SystemSetting::read('current_budget'));
    }

    public function test_user_can_increase_the_management_budget_from_account_page(): void
    {
        SystemSetting::write('current_budget', '12500.50');

        $this->actingAs(User::factory()->create())
            ->put(route('account.budget.update'), [
                'action' => 'increase',
                'increase_amount' => '250.25',
            ])
            ->assertRedirect(route('account.index'))
            ->assertSessionHas('success');

        $this->assertEquals(12750.75, (float) SystemSetting::read('current_budget'));
    }

    public function test_increasing_budget_cannot_exceed_the_supported_maximum(): void
    {
        SystemSetting::write('current_budget', '999999999.00');

        $this->actingAs(User::factory()->create())
            ->from(route('account.index'))
            ->put(route('account.budget.update'), [
                'action' => 'increase',
                'increase_amount' => '1.00',
            ])
            ->assertRedirect(route('account.index'))
            ->assertSessionHasErrors('increase_amount');

        $this->assertEquals(999999999.00, (float) SystemSetting::read('current_budget'));
    }

}
