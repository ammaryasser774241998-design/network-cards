<?php

namespace Tests\Feature;

use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerRechargeTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_customer_recharge_updates_balance_and_records_financial_activity(): void
    {
        $customer = Customer::create([
            'full_name' => 'عميل اختبار',
            'phone_number' => '777123456',
            'bank_account_name' => 'عميل اختبار',
            'balance' => 100,
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('customers.recharge', $customer->id), ['amount' => 250])
            ->assertRedirect(route('customers.index'));

        $this->assertSame(350.0, (float) $customer->fresh()->balance);
        $this->assertDatabaseHas('bank_transactions', [
            'customer_id' => $customer->id,
            'wallet_name' => 'شحن يدوي',
            'amount' => 250,
            'status' => 'completed',
        ]);
        $this->assertDatabaseCount('sms_outbox', 0);
        $this->assertSame(1, BankTransaction::count());
    }
}
