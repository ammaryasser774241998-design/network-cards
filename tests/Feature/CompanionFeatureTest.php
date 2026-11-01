<?php

namespace Tests\Feature;

use App\Models\CardCategory;
use App\Models\Customer;
use App\Models\SmsOutbox;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanionFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_companion_sms_delivery_endpoints_are_disabled(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-companion')->plainTextToken;
        $message = SmsOutbox::create([
            'phone_number' => '777123456',
            'message' => 'بطاقة تجريبية',
        ]);

        $this->withToken($token)->getJson('/api/companion/sms/pending')->assertNotFound();
        $this->withToken($token)->postJson("/api/companion/sms/{$message->id}/result", [
            'status' => 'sent',
            'external_id' => 'android-msg-1',
        ])->assertNotFound();

        $this->assertDatabaseHas('sms_outbox', [
            'id' => $message->id,
            'status' => 'pending',
            'external_id' => null,
        ]);
    }

    public function test_companion_contact_sync_creates_and_updates_customers(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-companion')->plainTextToken;

        $this->withToken($token)->postJson('/api/companion/contacts/sync', [
            'contacts' => [
                ['name' => 'عميل CRM', 'phone' => '+967 777 654321'],
            ],
        ])->assertOk()->assertJsonPath('created', 1);

        $customer = Customer::firstOrFail();
        $this->assertSame('967777654321', $customer->phone_number);
        $this->assertSame('عميل CRM', $customer->full_name);
    }

    public function test_companion_contact_sync_registers_a_previous_direct_sale_guest(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-companion')->plainTextToken;
        $customer = Customer::create([
            'full_name' => 'زبون 777654321',
            'phone_number' => '777654321',
            'bank_account_name' => 'زبون 777654321',
            'is_guest' => true,
        ]);

        $this->withToken($token)->postJson('/api/companion/contacts/sync', [
            'contacts' => [
                ['name' => 'عميل مسجل الآن', 'phone' => '777654321'],
            ],
        ])->assertOk();

        $this->assertFalse($customer->fresh()->is_guest);
    }

    public function test_simulation_matches_configured_wallet_without_creating_a_sale(): void
    {
        $user = User::factory()->create();
        $category = CardCategory::create([
            'name' => 'فئة تجريب',
            'price' => 700,
        ]);
        SystemSetting::write('wallet_rules', [[
            'name' => 'محفظة تجريبية',
            'sender_pattern' => '~DemoWallet~',
            'amount_pattern' => '~المبلغ:\s*(\d+)~u',
        ]]);

        $this->actingAs($user)->post(route('settings.simulate'), [
            'sender' => 'DemoWallet',
            'message' => 'تم الاستلام. المبلغ: 700',
        ])->assertRedirect()->assertSessionHas('simulation', function (array $simulation) use ($category): bool {
            return $simulation['parsed']['amount'] === 700.0
                && $simulation['category'] === $category->name
                && $simulation['available'] === 0;
        });

        $this->assertDatabaseCount('bank_transactions', 0);
        $this->assertDatabaseCount('card_sales', 0);
    }
}
