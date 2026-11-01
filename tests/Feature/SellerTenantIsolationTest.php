<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sellers_can_only_view_and_change_customers_in_their_own_tenant(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        $ownerCustomer = Customer::create([
            'full_name' => 'عميل الشبكة الأولى',
            'phone_number' => '777123456',
            'bank_account_name' => 'عميل الشبكة الأولى',
        ]);

        $seller = User::factory()->create(['is_admin' => false]);
        $this->assertTrue($seller->fresh()->is_active);
        $this->assertNotNull($seller->tenant_id);
        $this->actingAs($seller)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertDontSee('عميل الشبكة الأولى');

        $this->delete(route('customers.destroy', $ownerCustomer))
            ->assertNotFound();

        $this->assertDatabaseHas('customers', [
            'id' => $ownerCustomer->id,
            'full_name' => 'عميل الشبكة الأولى',
        ]);
    }

    public function test_sellers_can_reuse_customer_phone_numbers_without_sharing_records(): void
    {
        $firstSeller = User::factory()->create(['is_admin' => false]);
        $this->actingAs($firstSeller);
        $firstCustomer = Customer::create([
            'full_name' => 'عميل البائع الأول',
            'phone_number' => '777555111',
            'bank_account_name' => 'عميل البائع الأول',
        ]);

        $secondSeller = User::factory()->create(['is_admin' => false]);
        $this->actingAs($secondSeller);
        $secondCustomer = Customer::create([
            'full_name' => 'عميل البائع الثاني',
            'phone_number' => '777555111',
            'bank_account_name' => 'عميل البائع الثاني',
        ]);

        $this->assertNotSame($firstCustomer->tenant_id, $secondCustomer->tenant_id);
        $this->assertSame(1, Customer::count());
        $this->assertSame('عميل البائع الثاني', Customer::first()->full_name);
    }

    public function test_customer_phone_validation_is_limited_to_the_current_seller(): void
    {
        $firstSeller = User::factory()->create(['is_admin' => false]);
        $this->actingAs($firstSeller)->post(route('customers.store'), [
            'full_name' => 'عميل البائع الأول',
            'phone_number' => '777555222',
            'status' => 'active',
        ])->assertRedirect();

        $secondSeller = User::factory()->create(['is_admin' => false]);
        $this->actingAs($secondSeller)->post(route('customers.store'), [
            'full_name' => 'عميل البائع الثاني',
            'phone_number' => '777555222',
            'status' => 'active',
        ])->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('customers', 2);
    }

    public function test_seller_settings_are_isolated_and_only_platform_admin_can_manage_sellers(): void
    {
        $firstSeller = User::factory()->create(['is_admin' => false]);
        $this->actingAs($firstSeller);
        SystemSetting::write('network_name', 'شبكة البائع الأول');

        $secondSeller = User::factory()->create(['is_admin' => false]);
        $this->actingAs($secondSeller);
        $this->assertSame('شبكتك', SystemSetting::read('network_name', 'شبكتك'));
        $this->get(route('sellers.index'))->assertForbidden();

        $admin = User::factory()->create();
        $this->actingAs($admin)
            ->get(route('sellers.index'))
            ->assertOk()
            ->assertSee('حسابات البائعين')
            ->assertSee('إنشاء حساب بائع');
    }

    public function test_platform_admin_can_create_and_suspend_a_separate_seller_account(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('sellers.store'), [
                'name' => 'بائع جديد',
                'email' => 'seller@example.test',
                'phone_number' => '777000555',
                'password' => 'Secure-pass-2026',
                'password_confirmation' => 'Secure-pass-2026',
            ])
            ->assertRedirect();

        $seller = User::where('email', 'seller@example.test')->firstOrFail();
        $this->assertFalse($seller->is_admin);
        $this->assertSame('seller', $seller->account_type);
        $this->assertNotSame($admin->tenant_id, $seller->tenant_id);

        $this->actingAs($admin)
            ->patch(route('sellers.status', $seller), ['is_active' => 0])
            ->assertRedirect();

        $this->assertFalse($seller->fresh()->is_active);
    }
}
