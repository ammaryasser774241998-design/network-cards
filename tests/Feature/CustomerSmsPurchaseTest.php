<?php

namespace Tests\Feature;

use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSmsPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_customer_from_unknown_deposit_and_credit_the_deposit_once(): void
    {
        $user = User::factory()->create();
        $deposit = BankTransaction::create([
            'sender_name_raw' => 'بنك الاختبار',
            'sender_customer_name' => 'عميل جديد',
            'sender_phone' => '777123456',
            'amount' => 2000,
            'raw_message' => 'إشعار إيداع',
            'status' => 'pending_customer',
        ]);

        $this->actingAs($user)
            ->get(route('transfers.index'))
            ->assertOk()
            ->assertSee('هل تريد تسجيل العميل كمشترك وإضافة الإيداع إلى رصيده؟')
            ->assertSee('عميل جديد');

        $this->actingAs($user)
            ->post(route('transfers.register-customer', $deposit), [
                'full_name' => 'عميل جديد',
                'phone_number' => '+967 777 123 456',
                'amount' => 2000,
                'card_delivery_enabled' => '1',
            ])
            ->assertRedirect();

        $customer = Customer::where('phone_number', '967777123456')->firstOrFail();
        $this->assertSame(2000.0, (float) $customer->balance);
        $this->assertTrue($customer->card_delivery_enabled);
        $this->assertDatabaseHas('bank_transactions', [
            'id' => $deposit->id,
            'customer_id' => $customer->id,
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->post(route('transfers.register-customer', $deposit), [
                'full_name' => 'عميل جديد',
                'phone_number' => '967777123456',
                'amount' => 2000,
            ])
            ->assertStatus(409);
        $this->assertSame(2000.0, (float) $customer->fresh()->balance);
    }

    public function test_owner_can_keep_unknown_deposit_unlinked_without_creating_a_customer(): void
    {
        $user = User::factory()->create();
        $deposit = BankTransaction::create([
            'sender_phone' => '777000111',
            'amount' => 500,
            'raw_message' => 'إشعار إيداع',
            'status' => 'pending_customer',
        ]);

        $this->actingAs($user)
            ->post(route('transfers.skip-customer', $deposit))
            ->assertRedirect();

        $this->assertDatabaseHas('bank_transactions', [
            'id' => $deposit->id,
            'customer_id' => null,
            'status' => 'completed',
        ]);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_manual_review_of_known_customer_deposit_credits_balance_only_after_approval(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-companion')->plainTextToken;
        $customer = Customer::create([
            'full_name' => 'عميل مسجل',
            'phone_number' => '777123456',
            'bank_account_name' => 'عميل مسجل',
            'balance' => 300,
        ]);

        $response = $this->withToken($token)->postJson('/api/companion/bank-payment', [
            'sender_name' => 'محفظة الاختبار',
            'customer_phone' => '777123456',
            'amount' => 500,
            'raw_message' => 'إيداع عميل مسجل',
            'ref_number' => 'MANUAL-REVIEW-1',
        ]);

        $response->assertStatus(202)->assertJsonPath('status', 'pending_customer');
        $this->assertSame(300.0, (float) $customer->fresh()->balance);
        $depositId = $response->json('transaction_id');

        $this->actingAs($user)
            ->post(route('transfers.approve', $depositId), ['amount' => 500])
            ->assertRedirect();

        $this->assertSame(800.0, (float) $customer->fresh()->balance);
        $this->assertDatabaseHas('bank_transactions', [
            'id' => $depositId,
            'status' => 'completed',
            'amount' => 500,
        ]);
    }

    public function test_customer_commands_over_sms_are_disabled(): void
    {
        $token = User::factory()->create()->createToken('test-companion')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/companion/incoming-sms', [
                'sender' => '777999111',
                'raw_message' => 'كرت ابو 2000',
                'received_at' => '2026-10-10T01:03:00Z',
            ])->assertNotFound();

        $this->assertDatabaseCount('bank_transactions', 0);
        $this->assertDatabaseCount('card_sales', 0);
        $this->assertDatabaseCount('sms_outbox', 0);
    }
}
