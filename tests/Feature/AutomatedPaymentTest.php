<?php

namespace Tests\Feature;

use App\Models\CardCategory;
use App\Models\CardSale;
use App\Models\Customer;
use App\Models\PhoneExclusion;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AutomatedPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(fn () => Http::response([], 500));
    }

    public function test_configured_bank_sms_credits_registered_customer_and_notifies_both_parties(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-companion')->plainTextToken;
        $category = CardCategory::create([
            'name' => 'فئة 1500',
            'price' => 1500,
            'cost_price' => 900,
        ]);
        $voucher = Voucher::create([
            'category_id' => $category->id,
            'pin_code' => 'BANK-123',
            'status' => 'available',
        ]);
        $customer = Customer::create([
            'full_name' => 'عميل اختبار',
            'phone_number' => '777123456',
            'bank_account_name' => 'عميل اختبار',
            'additional_phones' => ['777000456'],
        ]);
        SystemSetting::write('automation_enabled', true);
        SystemSetting::write('manager_phone', '777000111');
        SystemSetting::write('network_name', 'شبكة الاختبار');
        SystemSetting::write('wallet_rules', [[
            'name' => 'محفظة الاختبار',
            'sender_pattern' => '~TestWallet~i',
            'amount_pattern' => '~المبلغ:\s*([\d,]+)~u',
            'reference_pattern' => '~رقم:\s*([A-Z0-9-]+)~u',
            'customer_phone_pattern' => '~هاتف:\s*(\d+)~u',
        ]]);

        $payload = [
            'sender' => 'TestWallet',
            'raw_message' => 'تم استلام المبلغ: 1,500 هاتف: 777000456 رقم: REF-1',
            'received_at' => '2026-10-09T10:00:00Z',
            'source_device' => 'android-test',
        ];

        $first = $this->withToken($token)->postJson('/api/companion/bank-sms', $payload);
        $first->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('balance', 1500);

        $duplicate = $this->withToken($token)->postJson('/api/companion/bank-sms', $payload);
        $duplicate->assertOk()->assertJsonPath('duplicate', true);

        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'status' => 'available',
        ]);
        $this->assertDatabaseHas('bank_transactions', [
            'ref_number' => 'REF-1',
            'status' => 'completed',
            'customer_id' => $customer->id,
        ]);
        $this->assertSame(1500.0, (float) $customer->fresh()->balance);
        $this->assertDatabaseCount('card_sales', 0);
        $this->assertDatabaseCount('sms_outbox', 0);
        $this->actingAs($user)->get(route('reports.transfers', ['phone' => '777000456', 'type' => 'deposit']))
            ->assertOk()
            ->assertSee('تمت تغذية رصيد المشترك المسجل تلقائياً');
    }

    public function test_unconfigured_or_unmatched_bank_messages_are_not_sold_automatically(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-companion')->plainTextToken;
        $category = CardCategory::create([
            'name' => 'فئة 200',
            'price' => 200,
        ]);
        $voucher = Voucher::create([
            'category_id' => $category->id,
            'pin_code' => 'SAFE-123',
            'status' => 'available',
        ]);

        SystemSetting::write('wallet_rules', [[
            'name' => 'محفظة الاختبار',
            'sender_pattern' => '~KnownWallet~i',
            'amount_pattern' => '~المبلغ:\s*([\d,]+)~u',
        ]]);

        $response = $this->withToken($token)->postJson('/api/companion/bank-sms', [
            'sender' => 'KnownWallet',
            'raw_message' => 'إشعار لا يحتوي مبلغاً مطابقاً',
            'received_at' => '2026-10-09T10:05:00Z',
        ]);

        $response->assertStatus(202)->assertJsonPath('status', 'pending_customer');
        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'status' => 'available']);
        $this->assertSame(0, CardSale::count());
    }

    public function test_non_bank_sms_senders_are_ignored(): void
    {
        $token = User::factory()->create()->createToken('test-companion')->plainTextToken;
        SystemSetting::write('wallet_rules', [[
            'name' => 'محفظة الاختبار',
            'sender_pattern' => '~KnownWallet~i',
            'amount_pattern' => '~المبلغ:\s*([\d,]+)~u',
        ]]);

        $this->withToken($token)->postJson('/api/companion/bank-sms', [
            'sender' => 'CustomerPhone',
            'raw_message' => 'كرت ابو 2000',
        ])->assertOk()->assertJsonPath('status', 'ignored');

        $this->assertDatabaseCount('bank_transactions', 0);
    }

    public function test_known_customer_deposit_is_credited_even_when_excluded_from_card_delivery(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-companion')->plainTextToken;
        $category = CardCategory::create([
            'name' => 'فئة 500',
            'price' => 500,
        ]);
        $voucher = Voucher::create([
            'category_id' => $category->id,
            'pin_code' => 'EXCLUDED-500',
            'status' => 'available',
        ]);
        $customer = Customer::create([
            'full_name' => 'عميل مستثنى',
            'phone_number' => '777123456',
            'bank_account_name' => 'عميل مستثنى',
        ]);
        PhoneExclusion::create(['phone_number' => $customer->phone_number]);
        SystemSetting::write('automation_enabled', true);

        $response = $this->withToken($token)->postJson('/api/companion/bank-payment', [
            'sender_name' => 'TestWallet',
            'wallet_name' => 'محفظة الاختبار',
            'customer_phone' => $customer->phone_number,
            'customer_name' => $customer->full_name,
            'amount' => 500,
            'raw_message' => 'حوالة لرقم مستثنى',
            'ref_number' => 'EXCLUDED-REF',
        ]);

        $response->assertOk()->assertJsonPath('status', 'completed');
        $this->assertDatabaseHas('bank_transactions', [
            'ref_number' => 'EXCLUDED-REF',
            'customer_id' => $customer->id,
            'status' => 'completed',
        ]);
        $this->assertSame(500.0, (float) $customer->fresh()->balance);
        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'status' => 'available']);
        $this->assertDatabaseCount('card_sales', 0);
        $this->assertDatabaseCount('sms_outbox', 0);
    }

    public function test_known_customer_deposit_matches_by_name_when_sender_phone_is_unavailable(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-companion')->plainTextToken;
        $customer = Customer::create([
            'full_name' => 'اسامه عدنان',
            'phone_number' => '967777555111',
            'bank_account_name' => 'اسامه عدنان',
            'balance' => 2500,
        ]);
        SystemSetting::write('automation_enabled', true);
        SystemSetting::write('network_name', 'شبكة بيروت');

        $response = $this->withToken($token)->postJson('/api/companion/bank-payment', [
            'sender_name' => 'Kuraimi',
            'customer_name' => 'اسامه عدنان',
            'amount' => 30000,
            'raw_message' => 'أودع/اسامه عدنان لحسابك30,000.00 YERرصيدك30,000.50YER',
            'ref_number' => 'KURAIMI-NAME-MATCH-1',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('customer_id', $customer->id)
            ->assertJsonPath('balance', 32500);
        $this->assertSame(32500.0, (float) $customer->fresh()->balance);
        $this->assertDatabaseHas('bank_transactions', [
            'ref_number' => 'KURAIMI-NAME-MATCH-1',
            'customer_id' => $customer->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseCount('sms_outbox', 0);
    }

    public function test_unknown_deposit_is_not_credited_or_replied_to_before_owner_approval(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-companion')->plainTextToken;
        SystemSetting::write('automation_enabled', true);

        $response = $this->withToken($token)->postJson('/api/companion/bank-payment', [
            'sender_name' => 'TestWallet',
            'customer_phone' => '777999111',
            'amount' => 700,
            'raw_message' => 'حوالة من عميل جديد',
        ]);

        $response->assertStatus(202)->assertJsonPath('status', 'pending_customer');
        $this->assertDatabaseHas('bank_transactions', [
            'sender_phone' => '777999111',
            'amount' => 700,
            'status' => 'pending_customer',
        ]);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('sms_outbox', 0);
    }

    public function test_companion_financial_api_requires_an_admin_access_token(): void
    {
        $this->postJson('/api/companion/bank-sms', [
            'sender' => 'Bank',
            'raw_message' => 'إشعار حوالة',
        ])->assertUnauthorized();
    }
}
