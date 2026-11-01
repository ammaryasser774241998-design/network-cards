<?php

namespace Tests\Feature;

use App\Models\CardCategory;
use App\Models\Customer;
use App\Models\User;
use App\Models\Voucher;
use App\Models\PhoneExclusion;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DirectSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_controls_are_shown_inside_direct_sales_page_instead_of_top_header(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('direct_sales.index'))
            ->assertOk()
            ->assertSee('id="header-controls"', false)
            ->assertSee('id="budget-toggle"', false)
            ->assertSee('data-confirm-automation', false)
            ->assertSee('fa-robot');

        $content = $response->getContent();
        $headerEnd = strpos($content, '</header>');
        $controlsPosition = strpos($content, 'id="header-controls"');
        $salesTitlePosition = strpos($content, 'البيع المباشر وإدارة الفئات');
        $categoriesPosition = strpos($content, 'عدد الفئات المتاحة');

        $this->assertNotFalse($headerEnd);
        $this->assertNotFalse($controlsPosition);
        $this->assertNotFalse($salesTitlePosition);
        $this->assertNotFalse($categoriesPosition);
        $this->assertGreaterThan($headerEnd, $controlsPosition);
        $this->assertGreaterThan($salesTitlePosition, $controlsPosition);
        $this->assertGreaterThan($controlsPosition, $categoriesPosition);
    }

    public function test_direct_sale_to_unregistered_number_does_not_create_customer(): void
    {
        SystemSetting::write('current_budget', '2000.00');
        SystemSetting::write('network_name', 'شبكة الاختبار');
        SystemSetting::write('sms_template', '{network} — {category} — {pin}{serial_suffix}');
        $category = CardCategory::create([
            'name' => 'فئة اختبار',
            'price' => 1500,
        ]);
        $voucher = Voucher::create([
            'category_id' => $category->id,
            'pin_code' => 'TEST-123',
            'serial_number' => 'SERIAL-123',
            'status' => 'available',
        ]);

        $response = $this->actingAs(User::factory()->create([
            'phone_number' => '777000001',
        ]))->post(route('direct_sales.transfer'), [
            'phone' => '777123456',
            'category_id' => $category->id,
            'quantity' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('last_transfer', function (array $sale): bool {
            return $sale['customer_phone'] === '777123456'
                && $sale['cards'][0]['pin_code'] === 'TEST-123'
                && (float) $sale['price'] === 1500.0;
        });

        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'status' => 'sold',
            'customer_id' => null,
        ]);
        $this->assertNotNull($voucher->fresh()->sold_at);
        $this->assertDatabaseHas('card_sales', [
            'voucher_id' => $voucher->id,
            'customer_id' => null,
            'amount' => 1500,
            'source' => 'direct',
        ]);
        $this->assertSame('Asia/Riyadh', \App\Models\CardSale::firstOrFail()->sold_at->timezoneName);
        $this->assertDatabaseCount('sms_outbox', 0);
        $this->assertEquals(500, (float) SystemSetting::read('current_budget'));
        $this->assertDatabaseMissing('customers', ['phone_number' => '777123456']);

        $report = $this->get(route('reports.transfers'));
        $report->assertOk();
        $report->assertSee('TEST-123');
        $report->assertSee('777123456');
        $report->assertSee('1,500.00');
    }

    public function test_transfer_report_can_filter_deposits_by_date(): void
    {
        $customer = Customer::create([
            'full_name' => 'عميل التقرير',
            'phone_number' => '777123459',
            'bank_account_name' => 'عميل التقرير',
        ]);
        $now = now();

        DB::table('bank_transactions')->insert([
            'sender_name_raw' => 'مرسل تجريبي',
            'sender_phone' => '777987654',
            'customer_id' => $customer->id,
            'amount' => 2500,
            'raw_message' => 'حوالة اختبار',
            'status' => 'completed',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        for ($index = 2; $index <= 16; $index++) {
            DB::table('bank_transactions')->insert([
                'sender_name_raw' => 'مرسل تجريبي',
                'sender_phone' => '777987654',
                'customer_id' => $customer->id,
                'amount' => 2500,
                'raw_message' => "حوالة اختبار {$index}",
                'status' => 'completed',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $response = $this->actingAs(User::factory()->create())->get(route('reports.transfers', [
            'from_date' => $now->toDateString(),
            'to_date' => $now->toDateString(),
            'type' => 'deposit',
            'phone' => '777987654',
        ]));

        $response->assertOk();
        $response->assertSee('حوالة اختبار');
        $this->get(route('reports.transfers', [
            'from_date' => $now->toDateString(),
            'to_date' => $now->toDateString(),
            'type' => 'deposit',
            'phone' => '777987654',
            'print' => 1,
        ]))
            ->assertOk()
            ->assertSee('حوالة اختبار 16')
            ->assertSee('عدد العمليات في التقرير');

        $this->get(route('reports.transfers', [
            'to_date' => $now->toDateString(),
            'type' => 'deposit',
        ]))->assertOk();
    }

    public function test_financial_report_can_search_by_customer_name_and_highlight_matches(): void
    {
        $customer = Customer::create([
            'full_name' => 'عميل التقارير',
            'phone_number' => '777987650',
            'bank_account_name' => 'عميل التقارير',
        ]);
        $category = CardCategory::create(['name' => 'فئة التقرير', 'price' => 500]);
        $voucher = Voucher::create([
            'category_id' => $category->id,
            'pin_code' => 'REPORT-CARD',
            'status' => 'sold',
            'customer_id' => $customer->id,
            'sold_at' => now(),
        ]);
        \App\Models\CardSale::create([
            'voucher_id' => $voucher->id,
            'customer_id' => $customer->id,
            'customer_phone' => $customer->phone_number,
            'category_name' => $category->name,
            'pin_code' => $voucher->pin_code,
            'amount' => 500,
            'cost_amount' => 100,
            'source' => 'direct',
            'sold_at' => now(),
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('reports.transfers', ['phone' => 'التقارير']))
            ->assertOk()
            ->assertSee('REPORT-CARD')
            ->assertSee('بحث باسم العميل أو رقم الهاتف')
            ->assertSee('report-search-highlight-target')
            ->assertSee('id="report-customer-match"', false)
            ->assertSee('العميل أو الرقم المطابق في النتائج')
            ->assertSee('data-customer-name="عميل التقارير"', false);

        $this->get(route('reports.transfers', ['phone' => '777 987 650']))
            ->assertOk()
            ->assertSee('REPORT-CARD');
    }

    public function test_financial_report_summary_counts_registered_customers_and_ranks_categories_by_cards_sold(): void
    {
        $registered = Customer::create([
            'full_name' => 'عميل الفترة',
            'phone_number' => '777987651',
            'bank_account_name' => 'عميل الفترة',
        ]);
        Customer::create([
            'full_name' => 'ضيف بيع مباشر',
            'phone_number' => '777987652',
            'bank_account_name' => 'ضيف بيع مباشر',
            'is_guest' => true,
        ]);

        $popularCategory = CardCategory::create(['name' => 'الأكثر عدداً', 'price' => 100]);
        $highRevenueCategory = CardCategory::create(['name' => 'الأعلى إيراداً', 'price' => 1000]);
        $lowCategory = CardCategory::create(['name' => 'الأقل عدداً', 'price' => 50]);

        foreach ([
            [$popularCategory, 100, 'POPULAR-A'],
            [$popularCategory, 100, 'POPULAR-B'],
            [$highRevenueCategory, 1000, 'REVENUE-A'],
            [$lowCategory, 50, 'LOW-A'],
        ] as [$category, $amount, $pin]) {
            \App\Models\CardSale::create([
                'customer_id' => $registered->id,
                'customer_phone' => $registered->phone_number,
                'category_name' => $category->name,
                'pin_code' => $pin,
                'amount' => $amount,
                'cost_amount' => 0,
                'source' => 'direct',
                'sold_at' => now(),
            ]);
        }

        $response = $this->actingAs(User::factory()->create())
            ->get(route('reports.transfers', [
                'from_date' => now()->toDateString(),
                'to_date' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertViewHas('newCustomersCount', 1)
            ->assertViewHas('bestSellingCategory', fn ($category): bool =>
                $category->category_name === 'الأكثر عدداً'
                && (int) $category->cards_count === 2)
            ->assertViewHas('leastSellingCategory', fn ($category): bool =>
                $category->category_name === 'الأقل عدداً'
                && (int) $category->cards_count === 1);

        $response->assertSee('bg-slate-900');
    }

    public function test_direct_sale_does_not_create_customer_when_category_has_no_stock(): void
    {
        $category = CardCategory::create([
            'name' => 'فئة فارغة',
            'price' => 1500,
        ]);

        $response = $this->actingAs(User::factory()->create([
            'phone_number' => '777000002',
        ]))->from(route('direct_sales.index'))
            ->post(route('direct_sales.transfer'), [
                'phone' => '777123456',
                'category_id' => $category->id,
                'quantity' => '1',
            ]);

        $response->assertRedirect(route('direct_sales.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('vouchers', 0);
    }

    public function test_direct_sale_deducts_total_amount_from_registered_customer_balance(): void
    {
        SystemSetting::write('current_budget', '1000.00');
        $category = CardCategory::create([
            'name' => 'فئة متعددة',
            'price' => 300,
            'low_stock_threshold' => 0,
        ]);
        $customer = Customer::create([
            'full_name' => 'عميل مسجل',
            'phone_number' => '777123458',
            'bank_account_name' => 'عميل مسجل',
            'balance' => 1000,
        ]);

        foreach (['BALANCE-A', 'BALANCE-B'] as $pin) {
            Voucher::create([
                'category_id' => $category->id,
                'pin_code' => $pin,
                'status' => 'available',
            ]);
        }

        $this->actingAs(User::factory()->create())->post(route('direct_sales.transfer'), [
            'phone' => $customer->phone_number,
            'category_id' => $category->id,
            'quantity' => '2',
        ])->assertRedirect();

        $this->assertEquals(400, (float) $customer->fresh()->balance);
        $this->assertEquals(1000, (float) SystemSetting::read('current_budget'));
        $this->assertDatabaseCount('card_sales', 2);
    }

    public function test_repeated_direct_sales_to_unregistered_number_deduct_system_budget_without_creating_customer(): void
    {
        SystemSetting::write('current_budget', '1000.00');
        $category = CardCategory::create([
            'name' => 'فئة زبون غير مسجل',
            'price' => 300,
            'low_stock_threshold' => 0,
        ]);
        foreach (['GUEST-A', 'GUEST-B'] as $pin) {
            Voucher::create([
                'category_id' => $category->id,
                'pin_code' => $pin,
                'status' => 'available',
            ]);
        }

        $user = User::factory()->create();
        foreach (['GUEST-A', 'GUEST-B'] as $pin) {
            $this->actingAs($user)->post(route('direct_sales.transfer'), [
                'phone' => '777123470',
                'category_id' => $category->id,
                'quantity' => 1,
            ])->assertRedirect()->assertSessionHas('last_transfer');
        }

        $this->assertEquals(400, (float) SystemSetting::read('current_budget'));
        $this->assertDatabaseMissing('customers', ['phone_number' => '777123470']);
        $this->assertDatabaseCount('card_sales', 2);
        $this->assertDatabaseHas('card_sales', [
            'customer_phone' => '777123470',
            'customer_id' => null,
        ]);
    }

    public function test_direct_sale_for_unregistered_customer_is_rejected_when_system_budget_is_insufficient(): void
    {
        SystemSetting::write('current_budget', '299.99');
        $category = CardCategory::create([
            'name' => 'فئة الميزانية',
            'price' => 300,
            'low_stock_threshold' => 0,
        ]);
        $voucher = Voucher::create([
            'category_id' => $category->id,
            'pin_code' => 'BUDGET-INSUFFICIENT',
            'status' => 'available',
        ]);

        $this->actingAs(User::factory()->create())
            ->from(route('direct_sales.index'))
            ->post(route('direct_sales.transfer'), [
                'phone' => '777123471',
                'category_id' => $category->id,
                'quantity' => 1,
            ])
            ->assertRedirect(route('direct_sales.index'))
            ->assertSessionHas('error');

        $this->assertEquals(299.99, (float) SystemSetting::read('current_budget'));
        $this->assertDatabaseMissing('customers', ['phone_number' => '777123471']);
        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'status' => 'available']);
        $this->assertDatabaseCount('card_sales', 0);
    }

    public function test_direct_sale_records_allowed_balance_shortfall_as_customer_debt(): void
    {
        $category = CardCategory::create([
            'name' => 'فئة آجلة',
            'price' => 300,
            'cost_price' => 150,
        ]);
        $customer = Customer::create([
            'full_name' => 'عميل آجل',
            'phone_number' => '777123460',
            'bank_account_name' => 'عميل آجل',
            'balance' => 250,
            'debt_balance' => 100,
            'debt_limit' => 200,
            'credit_sales_enabled' => true,
            'card_delivery_enabled' => true,
        ]);
        Voucher::create([
            'category_id' => $category->id,
            'pin_code' => 'CREDIT-300',
            'status' => 'available',
        ]);

        $this->actingAs(User::factory()->create())->post(route('direct_sales.transfer'), [
            'phone' => $customer->phone_number,
            'category_id' => $category->id,
            'quantity' => 1,
        ])->assertRedirect();

        $this->assertEquals(0, (float) $customer->fresh()->balance);
        $this->assertEquals(150, (float) $customer->fresh()->debt_balance);
        $this->assertDatabaseHas('customer_ledger_entries', [
            'customer_id' => $customer->id,
            'type' => 'charge',
            'amount' => 50,
        ]);
        $this->assertDatabaseHas('card_sales', [
            'customer_id' => $customer->id,
            'amount' => 300,
        ]);
    }

    public function test_direct_sale_applies_wholesale_price_and_sells_bonus_cards_without_sms(): void
    {
        $category = CardCategory::create([
            'name' => 'فئة جملة مع هدية',
            'price' => 300,
            'cost_price' => 150,
            'wholesale_price' => 250,
            'wholesale_min_quantity' => 2,
            'bonus_min_quantity' => 2,
            'bonus_cards' => 1,
        ]);
        $customer = Customer::create([
            'full_name' => 'عميل جملة',
            'phone_number' => '777123461',
            'bank_account_name' => 'عميل جملة',
            'balance' => 500,
        ]);
        foreach (['WHOLESALE-A', 'WHOLESALE-B', 'BONUS-C'] as $pin) {
            Voucher::create([
                'category_id' => $category->id,
                'pin_code' => $pin,
                'status' => 'available',
            ]);
        }

        $this->actingAs(User::factory()->create())->post(route('direct_sales.transfer'), [
            'phone' => $customer->phone_number,
            'category_id' => $category->id,
            'quantity' => 2,
        ])->assertRedirect()->assertSessionHas('last_transfer', function (array $sale): bool {
            return count($sale['cards']) === 3 && (float) $sale['price'] === 500.0;
        });

        $this->assertEquals(0, (float) $customer->fresh()->balance);
        $this->assertDatabaseCount('card_sales', 3);
        $this->assertDatabaseHas('card_sales', [
            'customer_id' => $customer->id,
            'amount' => 0,
            'source' => 'bonus',
        ]);
        $this->assertSame(0, \App\Models\SmsOutbox::count());
    }

    public function test_direct_sale_does_not_create_customer_or_sell_to_an_excluded_number(): void
    {
        $category = CardCategory::create([
            'name' => 'فئة مستثناة',
            'price' => 500,
        ]);
        $voucher = Voucher::create([
            'category_id' => $category->id,
            'pin_code' => 'EXCLUDED-DIRECT',
            'status' => 'available',
        ]);
        PhoneExclusion::create(['phone_number' => '777123499']);

        $this->actingAs(User::factory()->create())
            ->from(route('direct_sales.index'))
            ->post(route('direct_sales.transfer'), [
                'phone' => '777-123-499',
                'category_id' => $category->id,
                'quantity' => 1,
            ])
            ->assertRedirect(route('direct_sales.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'status' => 'available']);
        $this->assertDatabaseCount('card_sales', 0);
    }

    public function test_direct_sale_with_insufficient_registered_customer_balance_does_not_sell_cards(): void
    {
        $category = CardCategory::create([
            'name' => 'فئة اختبار',
            'price' => 300,
            'low_stock_threshold' => 0,
        ]);
        $customer = Customer::create([
            'full_name' => 'عميل رصيده قليل',
            'phone_number' => '777123459',
            'bank_account_name' => 'عميل رصيده قليل',
            'balance' => 250,
        ]);
        $voucher = Voucher::create([
            'category_id' => $category->id,
            'pin_code' => 'INSUFFICIENT-BALANCE',
            'status' => 'available',
        ]);

        $this->actingAs(User::factory()->create())->from(route('direct_sales.index'))
            ->post(route('direct_sales.transfer'), [
                'phone' => $customer->phone_number,
                'category_id' => $category->id,
                'quantity' => '1',
            ])
            ->assertRedirect(route('direct_sales.index'))
            ->assertSessionHas('error');

        $this->assertEquals(250, (float) $customer->fresh()->balance);
        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'status' => 'available',
        ]);
        $this->assertDatabaseCount('card_sales', 0);
        $this->assertDatabaseCount('sms_outbox', 0);
    }

    public function test_direct_sale_sells_all_requested_cards_without_sms_queueing(): void
    {
        SystemSetting::write('current_budget', '1000.00');
        $category = CardCategory::create([
            'name' => 'فئة متعددة',
            'price' => 300,
            'cost_price' => 100,
            'low_stock_threshold' => 0,
        ]);

        foreach (['PIN-A', 'PIN-B'] as $pin) {
            Voucher::create([
                'category_id' => $category->id,
                'pin_code' => $pin,
                'status' => 'available',
            ]);
        }

        $response = $this->actingAs(User::factory()->create())->post(route('direct_sales.transfer'), [
            'phone' => '777123457',
            'category_id' => $category->id,
            'quantity' => '2',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('last_transfer', function (array $sale): bool {
            return count($sale['cards']) === 2 && (float) $sale['price'] === 600.0;
        });
        $this->assertEquals(400, (float) SystemSetting::read('current_budget'));
        $this->assertDatabaseCount('card_sales', 2);
        $this->assertDatabaseCount('sms_outbox', 0);
        $this->assertDatabaseMissing('vouchers', ['status' => 'available']);
    }
}
