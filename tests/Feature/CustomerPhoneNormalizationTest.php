<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\CardCategory;
use App\Models\CardSale;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPhoneNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_phone_is_normalized_when_created(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('customers.store'), [
                'full_name' => 'عميل اختبار',
                'phone_number' => '+967 777-123-456',
                'status' => 'active',
            ])
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'full_name' => 'عميل اختبار',
            'phone_number' => '967777123456',
        ]);
    }

    public function test_customer_page_does_not_show_contact_sync_section(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('customers.index'))
            ->assertOk()
            ->assertDontSee('مزامنة جهات الاتصال')
            ->assertDontSee('name="contacts"', false)
            ->assertSee('قائمة العملاء المسجلين');
    }

    public function test_customer_tables_remain_horizontally_scrollable_on_mobile(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('.table-responsive {', false)
            ->assertSee('overflow-x: auto;', false)
            ->assertSee('-webkit-overflow-scrolling: touch;', false)
            ->assertSee('touch-action: pan-x;', false)
            ->assertSee('.table-responsive > table {', false)
            ->assertSee('display: table;', false)
            ->assertSee('min-width: 950px;', false);
    }

    public function test_success_flash_uses_navy_and_gold_popup_notification(): void
    {
        $this->actingAs(User::factory()->create())
            ->withSession(['success' => 'تم حذف العميل بنجاح.'])
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('id="operation-notification"', false)
            ->assertSee('تمت العملية بنجاح')
            ->assertSee('تم حذف العميل بنجاح.')
            ->assertSee('top-1/2')
            ->assertSee('-translate-y-1/2')
            ->assertSee('bg-slate-900')
            ->assertDontSee('alert-success');
    }

    public function test_customer_search_finds_additional_phone_numbers(): void
    {
        Customer::create([
            'full_name' => 'عميل إضافي',
            'phone_number' => '777000111',
            'bank_account_name' => 'عميل إضافي',
            'additional_phones' => ['967777123456'],
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('customers.index', ['search' => '+967 777 123 456']))
            ->assertOk()
            ->assertSee('عميل إضافي');
    }

    public function test_customer_list_does_not_include_legacy_direct_sale_guests(): void
    {
        Customer::create([
            'full_name' => 'عميل مسجل',
            'phone_number' => '777000112',
            'bank_account_name' => 'عميل مسجل',
        ]);
        Customer::create([
            'full_name' => 'زبون 777000113',
            'phone_number' => '777000113',
            'bank_account_name' => 'زبون 777000113',
            'is_guest' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('عميل مسجل')
            ->assertDontSee('زبون 777000113');
    }

    public function test_customer_can_be_deleted_while_preserving_financial_history(): void
    {
        $customer = Customer::create([
            'full_name' => 'عميل للحذف',
            'phone_number' => '777000114',
            'bank_account_name' => 'عميل للحذف',
        ]);
        $category = CardCategory::create(['name' => 'فئة محفوظة', 'price' => 100]);
        $voucher = Voucher::create([
            'category_id' => $category->id,
            'pin_code' => 'KEEP-SALE-HISTORY',
            'status' => 'sold',
            'customer_id' => $customer->id,
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
        $entry = CustomerLedgerEntry::create([
            'customer_id' => $customer->id,
            'type' => 'charge',
            'amount' => 100,
            'description' => 'قيد محفوظ بعد حذف العميل',
        ]);

        $this->actingAs(User::factory()->create())
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
        $this->assertDatabaseHas('card_sales', [
            'id' => $sale->id,
            'customer_id' => null,
            'customer_phone' => $customer->phone_number,
        ]);
        $this->assertDatabaseHas('customer_ledger_entries', [
            'id' => $entry->id,
            'customer_id' => null,
            'description' => 'قيد محفوظ بعد حذف العميل',
        ]);
        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'customer_id' => null,
            'status' => 'sold',
        ]);
    }
}
