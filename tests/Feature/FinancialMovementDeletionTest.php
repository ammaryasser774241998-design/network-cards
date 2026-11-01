<?php

namespace Tests\Feature;

use App\Models\BankTransaction;
use App\Models\CardCategory;
use App\Models\CardSale;
use App\Models\Customer;
use App\Models\SmsOutbox;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialMovementDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_movement_can_be_deleted_without_changing_stock_or_customer_balance(): void
    {
        $customer = Customer::create([
            'full_name' => 'عميل عملية بيع',
            'phone_number' => '777600001',
            'bank_account_name' => 'عميل عملية بيع',
            'balance' => 900,
        ]);
        $category = CardCategory::create(['name' => 'فئة الحذف', 'price' => 100]);
        $voucher = Voucher::create([
            'category_id' => $category->id,
            'pin_code' => 'SALE-DELETE-TEST',
            'status' => 'sold',
            'customer_id' => $customer->id,
            'sold_at' => now(),
        ]);
        $sale = CardSale::create([
            'voucher_id' => $voucher->id,
            'customer_id' => $customer->id,
            'customer_phone' => $customer->phone_number,
            'category_name' => $category->name,
            'pin_code' => $voucher->pin_code,
            'amount' => 100,
            'cost_amount' => 0,
            'source' => 'direct',
            'sold_at' => now(),
        ]);
        $message = SmsOutbox::create([
            'card_sale_id' => $sale->id,
            'phone_number' => $customer->phone_number,
            'message' => 'كرت المبيع',
        ]);

        $this->actingAs(User::factory()->create())
            ->delete(route('sales.destroy', $sale))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('card_sales', ['id' => $sale->id]);
        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'status' => 'sold',
            'customer_id' => $customer->id,
        ]);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'balance' => 900,
        ]);
        $this->assertDatabaseHas('sms_outbox', [
            'id' => $message->id,
            'card_sale_id' => null,
        ]);
    }

    public function test_transfer_movement_can_be_deleted_without_changing_customer_balance(): void
    {
        $customer = Customer::create([
            'full_name' => 'عميل حوالة',
            'phone_number' => '777600002',
            'bank_account_name' => 'عميل حوالة',
            'balance' => 700,
        ]);
        $transfer = BankTransaction::create([
            'sender_name_raw' => 'مرسل حوالة',
            'sender_phone' => $customer->phone_number,
            'customer_id' => $customer->id,
            'amount' => 300,
            'raw_message' => 'حوالة للاختبار',
            'status' => 'completed',
        ]);

        $this->actingAs(User::factory()->create())
            ->delete(route('transfers.destroy', $transfer))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('bank_transactions', ['id' => $transfer->id]);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'balance' => 700,
        ]);
    }

    public function test_all_financial_movement_tables_show_delete_actions(): void
    {
        $customer = Customer::create([
            'full_name' => 'عميل الجداول',
            'phone_number' => '777600003',
            'bank_account_name' => 'عميل الجداول',
        ]);
        $category = CardCategory::create(['name' => 'فئة الجداول', 'price' => 100]);
        $voucher = Voucher::create([
            'category_id' => $category->id,
            'pin_code' => 'TABLES-DELETE-TEST',
            'status' => 'sold',
            'customer_id' => $customer->id,
            'sold_at' => now(),
        ]);
        $sale = CardSale::create([
            'voucher_id' => $voucher->id,
            'customer_id' => $customer->id,
            'customer_phone' => $customer->phone_number,
            'category_name' => $category->name,
            'pin_code' => $voucher->pin_code,
            'amount' => 100,
            'cost_amount' => 0,
            'source' => 'direct',
            'sold_at' => now(),
        ]);
        $transfer = BankTransaction::create([
            'sender_name_raw' => 'مرسل فاشل',
            'sender_phone' => $customer->phone_number,
            'customer_id' => $customer->id,
            'amount' => 100,
            'raw_message' => 'حوالة فاشلة',
            'status' => 'failed',
        ]);

        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('reports.transfers'))
            ->assertOk()
            ->assertSee(route('sales.destroy', $sale))
            ->assertSee(route('transfers.destroy', $transfer))
            ->assertSee('id="delete-confirmation-modal"', false)
            ->assertSee('id="delete-confirmation-cancel"', false)
            ->assertSee('id="delete-confirmation-submit"', false);
        $this->get(route('transfers.index'))
            ->assertOk()
            ->assertSee(route('transfers.destroy', $transfer));
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('transfers.destroy', $transfer));
        $this->get(route('system-health.index'))
            ->assertOk()
            ->assertSee(route('transfers.destroy', $transfer));
    }
}
