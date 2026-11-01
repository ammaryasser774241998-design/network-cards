<?php

namespace App\Http\Controllers;

use App\Models\CardCategory;
use App\Models\Customer;
use App\Models\SystemSetting;
use App\Services\PaymentMessageParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use JsonException;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'automation_enabled' => SystemSetting::read('automation_enabled', false),
            'current_budget' => SystemSetting::read('current_budget', 0),
            'network_name' => SystemSetting::read('network_name', 'شبكتك'),
            'sms_template' => SystemSetting::read('sms_template', 'تم الشراء من {network}. الفئة: {category}. الرمز: {pin}{serial_suffix}'),
            'manager_phone' => SystemSetting::read('manager_phone', ''),
            'sim_slot_default' => SystemSetting::read('sim_slot_default', 0),
            'wallet_rules' => SystemSetting::read('wallet_rules', []),
        ];

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $rules = [
            'automation_enabled' => 'nullable|boolean',
            'current_budget' => 'nullable|numeric|min:0|max:999999999.99',
            'network_name' => 'required|string|max:100',
            'sms_template' => 'required|string|max:1000',
            'manager_phone' => 'nullable|string|max:30',
            'sim_slot_default' => 'required|integer|in:0,1',
            'wallet_rules_json' => 'nullable|string|max:20000',
        ];

        $validated = $request->validate($rules);

        try {
            $walletRules = json_decode($validated['wallet_rules_json'] ?? '[]', true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return back()->withErrors([
                'wallet_rules_json' => 'صيغة إعدادات المحافظ غير صحيحة؛ أدخل JSON صالحاً.',
            ])->withInput();
        }

        if (! is_array($walletRules) || ! array_is_list($walletRules)) {
            return back()->withErrors([
                'wallet_rules_json' => 'يجب أن تكون إعدادات المحافظ قائمة JSON.',
            ])->withInput();
        }

        foreach ($walletRules as $index => $rule) {
            if (! is_array($rule)) {
                return back()->withErrors([
                    'wallet_rules_json' => 'قاعدة المحفظة رقم '.($index + 1).' يجب أن تكون كائناً يحتوي إعدادات القاعدة.',
                ])->withInput();
            }

            $validator = Validator::make($rule, [
                'name' => 'required|string|max:100',
                'sender_pattern' => 'required|string|max:250',
                'amount_pattern' => 'required|string|max:250',
                'reference_pattern' => 'nullable|string|max:250',
                'customer_phone_pattern' => 'nullable|string|max:250',
                'customer_name_pattern' => 'nullable|string|max:250',
            ]);

            if ($validator->fails()) {
                return back()->withErrors([
                    'wallet_rules_json' => 'قاعدة المحفظة رقم '.($index + 1).' غير مكتملة.',
                ])->withInput();
            }

            if (! $this->validPatterns($rule)) {
                return back()->withErrors([
                    'wallet_rules_json' => 'نمط غير صالح في قاعدة المحفظة رقم '.($index + 1).'. أحط كل تعبير منتظم بفاصل، مثل ~النمط~u.',
                ])->withInput();
            }
        }

        SystemSetting::write('automation_enabled', $request->boolean('automation_enabled'));
        if ($request->exists('current_budget')) {
            SystemSetting::write(
                'current_budget',
                number_format((float) ($validated['current_budget'] ?? 0), 2, '.', '')
            );
        }
        SystemSetting::write('manager_phone', $validated['manager_phone'] ?? '');
        SystemSetting::write('network_name', $validated['network_name']);
        SystemSetting::write('sms_template', $validated['sms_template']);
        SystemSetting::write('sim_slot_default', (int) $validated['sim_slot_default']);
        SystemSetting::write('wallet_rules', $walletRules);

        return back()->with('success', 'تم حفظ الإعدادات بنجاح.');
    }

    public function simulate(Request $request, PaymentMessageParser $parser)
    {
        $validated = $request->validate([
            'sender' => 'required|string|max:255',
            'message' => 'required|string|max:10000',
        ]);

        $parsed = $parser->parse($validated['sender'], $validated['message']);
        $category = $parsed
            ? CardCategory::withCount([
                'vouchers as available_vouchers_count' => fn ($query) => $query->where('status', 'available'),
            ])->where('price', $parsed['amount'])->first()
            : null;

        $phone = $parsed['customer_phone'] ?? null;
        $customer = $phone
            ? Customer::where('phone_number', $phone)->first()
            : (! empty($parsed['customer_name'])
                ? Customer::where('full_name', 'LIKE', '%'.$parsed['customer_name'].'%')->first()
                : null);

        return back()->with('simulation', [
            'parsed' => $parsed,
            'category' => $category?->name,
            'available' => $category?->available_vouchers_count ?? 0,
            'customer' => $customer?->full_name,
            'automation_enabled' => SystemSetting::read('automation_enabled', false),
        ]);
    }

    private function validPatterns(array $rule): bool
    {
        foreach (['sender_pattern', 'amount_pattern', 'reference_pattern', 'customer_phone_pattern', 'customer_name_pattern'] as $key) {
            if (empty($rule[$key])) {
                continue;
            }

            if (@preg_match($rule[$key], '') === false) {
                return false;
            }
        }

        return true;
    }
}
