<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.index', [
            'user' => $request->user(),
            'currentBudget' => (float) SystemSetting::read('current_budget', 0),
        ]);
    }

    public function updateBudget(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['set', 'increase'])],
            'new_budget' => ['required_if:action,set', 'nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'increase_amount' => ['required_if:action,increase', 'nullable', 'numeric', 'min:0', 'max:999999999.99'],
        ], [
            'new_budget.required_if' => 'يرجى إدخال مبلغ الميزانية الجديد.',
            'new_budget.numeric' => 'يجب أن يكون مبلغ الميزانية رقمًا صحيحًا.',
            'new_budget.min' => 'لا يمكن أن تكون الميزانية أقل من صفر.',
            'new_budget.max' => 'الحد الأقصى للميزانية هو 999,999,999.99.',
            'increase_amount.required_if' => 'يرجى إدخال مبلغ الزيادة.',
            'increase_amount.numeric' => 'يجب أن يكون مبلغ الزيادة رقمًا صحيحًا.',
            'increase_amount.min' => 'لا يمكن أن تكون الزيادة أقل من صفر.',
            'increase_amount.max' => 'الحد الأقصى للزيادة هو 999,999,999.99.',
        ]);

        if ($validated['action'] === 'set') {
            SystemSetting::write(
                'current_budget',
                number_format((float) $validated['new_budget'], 2, '.', '')
            );

            return redirect()
                ->route('account.index')
                ->with('success', 'تم تحديث الميزانية بنجاح.');
        }

        $updated = DB::transaction(function () use ($validated): bool {
            $setting = SystemSetting::lock('current_budget', '0');
            $storedBudget = json_decode((string) $setting, true) ?? $setting;
            $storedBudget ??= 0;
            $currentCents = (int) round((float) $storedBudget * 100);
            $increaseCents = (int) round((float) $validated['increase_amount'] * 100);
            $maximumCents = 99999999999;

            if ($currentCents + $increaseCents > $maximumCents) {
                return false;
            }

            SystemSetting::write(
                'current_budget',
                number_format(($currentCents + $increaseCents) / 100, 2, '.', '')
            );

            return true;
        });

        if (! $updated) {
            return back()
                ->withErrors(['increase_amount' => 'تتجاوز الزيادة الحد الأقصى المسموح به للميزانية.'])
                ->withInput();
        }

        return redirect()
            ->route('account.index')
            ->with('success', 'تمت زيادة الميزانية بنجاح.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'كلمة المرور الحالية غير صحيحة.',
            'password.min' => 'كلمة المرور الجديدة يجب ألا تقل عن 6 أحرف.',
            'password.confirmed' => 'تأكيد كلمة المرور الجديدة غير متطابق.',
            'password.different' => 'يجب أن تختلف كلمة المرور الجديدة عن الحالية.',
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return redirect()
            ->route('account.index')
            ->with('success', 'تم تغيير كلمة مرور الحساب بنجاح.');
    }
}
