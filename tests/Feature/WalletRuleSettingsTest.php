<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\PaymentMessageParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletRuleSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_save_and_parse_kuraimi_deposit_message(): void
    {
        $user = User::factory()->create();
        $rules = [[
            'name' => 'KuraimilMB',
            'sender_pattern' => '~Kuraimi~i',
            'amount_pattern' => '~لحسابك\\s*([0-9,]+\\.[0-9]{2})\\s*YER~u',
            'customer_name_pattern' => '~أودع/(.+?)\\s*لحسابك~u',
            'reference_pattern' => '~رقم[:\\s]*(\\d+)~u',
        ]];

        $this->actingAs($user)
            ->from(route('settings.index'))
            ->put(route('settings.update'), $this->settingsPayload($rules))
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('success');

        $this->assertSame($rules, SystemSetting::read('wallet_rules'));

        $parsed = app(PaymentMessageParser::class)->parse(
            'Kuraimi',
            'أودع/اسامه عدنان لحسابك30,000.00 YERرصيدك30,000.50YER'
        );

        $this->assertSame([
            'wallet_name' => 'KuraimilMB',
            'amount' => 30000.0,
            'reference' => null,
            'customer_phone' => null,
            'customer_name' => 'اسامه عدنان',
        ], $parsed);
    }

    public function test_settings_reject_patterns_without_regex_delimiters(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('settings.index'))
            ->put(route('settings.update'), $this->settingsPayload([[
                'name' => 'KuraimilMB',
                'sender_pattern' => 'Kuraimi',
                'amount_pattern' => 'لحسابك\\s*([0-9,]+\\.[0-9]{2})\\s*YER',
            ]]))
            ->assertRedirect(route('settings.index'))
            ->assertSessionHasErrors([
                'wallet_rules_json' => 'نمط غير صالح في قاعدة المحفظة رقم 1. أحط كل تعبير منتظم بفاصل، مثل ~النمط~u.',
            ]);

        $this->assertSame([], SystemSetting::read('wallet_rules', []));
    }

    private function settingsPayload(array $walletRules): array
    {
        return [
            'manager_phone' => '',
            'sim_slot_default' => 0,
            'network_name' => 'شبكة الاختبار',
            'sms_template' => 'تم الشراء من {network}. الفئة: {category}. الرمز: {pin}{serial_suffix}',
            'unknown_customer_message' => 'تواصل مع الإدارة.',
            'wallet_rules_json' => json_encode(
                $walletRules,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ),
        ];
    }
}
