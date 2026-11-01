<?php

namespace Tests\Feature;

use App\Models\CardCategory;
use App\Models\Customer;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Voucher;
use App\Models\WorkShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_filters_sales_by_period_and_shows_inventory_and_recent_transfer_details(): void
    {
        $category = CardCategory::create([
            'name' => 'فئة 500',
            'price' => 500,
            'cost_price' => 300,
        ]);
        Voucher::create([
            'category_id' => $category->id,
            'pin_code' => 'AVAILABLE-500',
            'status' => 'available',
        ]);

        $this->createSale(500, now());
        $this->createSale(1000, now()->subDay());

        DB::table('bank_transactions')->insert([
            'ref_number' => 'REF-DASH-001',
            'sender_name_raw' => 'مرسل الاختبار',
            'sender_phone' => '777123456',
            'amount' => 500,
            'raw_message' => 'حوالة لوحة التحكم',
            'status' => 'pending_customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard', ['period' => 'today']))
            ->assertOk()
            ->assertSee('500.00')
            ->assertSee('REF-DASH-001')
            ->assertSee('فئة 500')
            ->assertSee('إجمالي المبيعات — اليوم');

        $this->get(route('dashboard', ['period' => 'all']))
            ->assertOk()
            ->assertSee('1,500.00')
            ->assertSee('إجمالي المبيعات — إجمالي المبيعات');
    }

    public function test_shared_layout_adapts_navigation_and_content_to_mobile_viewports(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('name="viewport"', false)
            ->assertSee('id="app-content"', false)
            ->assertSee('id="system-sidebar"', false)
            ->assertSee('@media (max-width: 1023px)', false)
            ->assertSee('@media (max-width: 767px)', false)
            ->assertSee('main table th,', false)
            ->assertSee('main table button,', false)
            ->assertSee('min-height: 2.25rem', false)
            ->assertSee('safe-area-inset-bottom', false);
    }

    public function test_dashboard_can_enable_and_disable_automatic_payment_processing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('dashboard', ['period' => 'week']))
            ->post(route('dashboard.automation'), [
                'enabled' => 1,
                'period' => 'week',
            ])
            ->assertRedirect(route('dashboard', ['period' => 'week']));

        $this->assertTrue(SystemSetting::read('automation_enabled'));

        $this->from(route('dashboard', ['period' => 'week']))
            ->post(route('dashboard.automation'), [
            'enabled' => 0,
            'period' => 'week',
        ])->assertRedirect(route('dashboard', ['period' => 'week']));

        $this->assertFalse(SystemSetting::read('automation_enabled'));
    }

    public function test_automation_control_is_only_available_on_direct_sales_and_returns_to_current_page(): void
    {
        $user = User::factory()->create();
        SystemSetting::write('automation_enabled', false);

        $this->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('<header class="sticky top-0 z-50', false)
            ->assertDontSee('id="makkah-clock"', false)
            ->assertDontSee('id="header-controls"', false)
            ->assertDontSee('id="budget-toggle"', false)
            ->assertSee('id="automation-confirmation-modal"', false)
            ->assertSee('automation-confirmation-cancel', false)
            ->assertSee('automation-confirmation-submit', false)
            ->assertDontSee('onsubmit="return confirm(', false)
            ->assertDontSee('يقوم البوت الآلي بالتقاط الحوالات وتوزيع الكروت');

        $this->get(route('direct_sales.index'))
            ->assertOk()
            ->assertSee('id="header-controls"', false)
            ->assertSee('robot-offline text-rose-500');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="makkah-clock"', false)
            ->assertSee('التوقيت الحالي في مكة المكرمة');

        $this->from(route('settings.index'))
            ->post(route('dashboard.automation'), ['enabled' => 1])
            ->assertRedirect(route('settings.index'));

        $this->assertTrue(SystemSetting::read('automation_enabled'));

        $this->get(route('direct_sales.index'))
            ->assertSee('إيقاف')
            ->assertSee('robot-gold-glow text-amber-300')
            ->assertDontSee('fa-face-smile')
            ->assertSee('يعمل');

        $this->from(route('settings.index'))
            ->followingRedirects()
            ->post(route('dashboard.automation'), ['enabled' => 0])
            ->assertOk()
            ->assertSee('id="operation-notification"', false)
            ->assertSee('تم إيقاف الروبوت الآلي.');

        $this->assertFalse(SystemSetting::read('automation_enabled'));

        $this->get(route('direct_sales.index'))
            ->assertSee('robot-offline text-rose-500');
    }

    public function test_enabling_automation_shows_gold_success_notification_with_customer_message(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('settings.index'))
            ->followingRedirects()
            ->post(route('dashboard.automation'), ['enabled' => 1])
            ->assertOk()
            ->assertSee('id="operation-notification"', false)
            ->assertSee('تمت العملية بنجاح')
            ->assertSee('تم تفعيل الروبوت، سوف يقوم الروبوت الآلي بالتقاط الحوالات وتوزيع الكروت للعملاء.')
            ->assertSee('border-amber-400/50');

        $this->assertTrue(SystemSetting::read('automation_enabled'));
    }

    public function test_admin_can_update_the_manual_dashboard_budget(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('settings.update'), [
                'current_budget' => '12500.50',
                'manager_phone' => '',
                'sim_slot_default' => 0,
                'network_name' => 'شبكة الاختبار',
                'sms_template' => 'تم الشراء من {network}. الفئة: {category}. الرمز: {pin}{serial_suffix}',
                'unknown_customer_message' => 'تواصل مع الإدارة.',
                'wallet_rules_json' => '[]',
            ])
            ->assertRedirect();

        $this->assertEquals(12500.50, (float) SystemSetting::read('current_budget'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('12,500.50')
            ->assertSee('ميزانية الإدارة (تُحدّث يدويًا)');
    }

    public function test_ledger_records_debt_and_rejects_entries_over_customer_limit(): void
    {
        $customer = Customer::create([
            'full_name' => 'عميل دفتر',
            'phone_number' => '777123456',
            'bank_account_name' => 'عميل دفتر',
            'debt_limit' => 100,
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('ledger.entries.store'), [
                'customer_id' => $customer->id,
                'type' => 'charge',
                'amount' => 80,
                'description' => 'دين اختبار',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(80, (float) $customer->fresh()->debt_balance);
        $this->assertDatabaseHas('customer_ledger_entries', [
            'customer_id' => $customer->id,
            'type' => 'charge',
            'amount' => 80,
            'description' => 'دين اختبار',
        ]);

        $this->post(route('ledger.entries.store'), [
            'customer_id' => $customer->id,
            'type' => 'charge',
            'amount' => 30,
            'description' => 'تجاوز السقف',
        ])->assertSessionHasErrors('amount');

        $this->assertEquals(80, (float) $customer->fresh()->debt_balance);
    }

    public function test_closing_shift_archives_metrics_and_starts_new_shift_without_deleting_records(): void
    {
        $openedAt = now()->subHours(2);
        $saleAt = now()->subHour();
        SystemSetting::write('shift_started_at', $openedAt->toDateTimeString());
        $this->createSale(500, $saleAt);

        DB::table('bank_transactions')->insert([
            'sender_name_raw' => 'مرسل وردية',
            'amount' => 700,
            'raw_message' => 'حوالة الوردية',
            'status' => 'completed',
            'created_at' => $saleAt,
            'updated_at' => $saleAt,
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('dashboard.close-shift'), ['period' => 'week'])
            ->assertRedirect(route('dashboard', ['period' => 'week']))
            ->assertSessionHas('success');

        $shift = WorkShift::firstOrFail();
        $this->assertEquals(500, (float) $shift->sales_amount);
        $this->assertSame(1, $shift->sales_count);
        $this->assertEquals(700, (float) $shift->transactions_amount);
        $this->assertSame(1, $shift->transactions_count);
        $this->assertSame(1, DB::table('card_sales')->count());
        $this->assertSame(1, DB::table('bank_transactions')->count());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('الوردية الحالية')
            ->assertSee('0.00');
        $this->get(route('shifts.index'))->assertOk()->assertSee('500.00');
    }

    private function createSale(float $amount, \Illuminate\Support\Carbon $soldAt): void
    {
        DB::table('card_sales')->insert([
            'customer_phone' => '777123456',
            'category_name' => 'فئة اختبار',
            'pin_code' => 'TEST-'.$amount.'-'.$soldAt->timestamp,
            'amount' => $amount,
            'cost_amount' => $amount / 2,
            'source' => 'direct',
            'sold_at' => $soldAt,
            'created_at' => $soldAt,
            'updated_at' => $soldAt,
        ]);
    }
}
