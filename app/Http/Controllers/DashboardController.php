<?php

namespace App\Http\Controllers;

use App\Models\BankTransaction;
use App\Models\CardCategory;
use App\Models\CardSale;
use App\Models\Customer;
use App\Models\SystemSetting;
use App\Models\Voucher;
use App\Models\WorkShift;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'period' => ['nullable', Rule::in(['today', 'yesterday', 'week', 'month', 'all'])],
        ]);
        $period = $validated['period'] ?? 'today';
        $today = today();
        [$periodStart, $periodEnd] = match ($period) {
            'yesterday' => [$today->copy()->subDay()->startOfDay(), $today->copy()->subDay()->endOfDay()],
            'week' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
            'month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            'all' => [null, null],
            default => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
        };
        $salesQuery = CardSale::query()
            ->when($periodStart, fn ($query) => $query->where('sold_at', '>=', $periodStart))
            ->when($periodEnd, fn ($query) => $query->where('sold_at', '<=', $periodEnd));
        $salesAmount = (clone $salesQuery)->sum('amount');
        $salesCount = (clone $salesQuery)->count();

        $availableStockCount = Voucher::where('status', 'available')->count();
        $stockCostValue = Voucher::query()->join('card_categories', 'vouchers.category_id', '=', 'card_categories.id')
            ->whereRaw('(card_categories.tenant_id = vouchers.tenant_id OR (card_categories.tenant_id IS NULL AND vouchers.tenant_id IS NULL))')
            ->where('vouchers.status', 'available')
            ->sum('card_categories.cost_price');

        $categories = CardCategory::withCount([
            'vouchers as available_vouchers_count' => fn ($query) => $query->where('status', 'available'),
        ])->orderBy('price')->get();

        $salesByDay = CardSale::query()
            ->whereBetween('sold_at', [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()])
            ->selectRaw('DATE(sold_at) as sale_date, SUM(amount) as amount')
            ->groupBy('sale_date')
            ->pluck('amount', 'sale_date');
        $dailySales = collect(range(6, 0))->map(function (int $daysAgo) use ($today, $salesByDay) {
            $date = $today->copy()->subDays($daysAgo);

            return [
                'label' => $date->format('m-d'),
                'amount' => (float) ($salesByDay->get($date->toDateString()) ?? 0),
            ];
        });

        $latestTransactions = BankTransaction::with(['customer', 'voucher'])
            ->latest()
            ->limit(10)
            ->get();
        $customerBalancesTotal = Customer::sum('balance');
        $shiftStartValue = SystemSetting::read('shift_started_at');
        $shiftStartedAt = $shiftStartValue
            ? Carbon::parse($shiftStartValue)
            : today()->startOfDay();
        $currentShift = [
            'sales_amount' => (float) CardSale::where('sold_at', '>=', $shiftStartedAt)->sum('amount'),
            'sales_count' => CardSale::where('sold_at', '>=', $shiftStartedAt)->count(),
            'transactions_count' => BankTransaction::where('created_at', '>=', $shiftStartedAt)->count(),
        ];
        $lastClosedShift = WorkShift::latest('closed_at')->first();

        return view('dashboard', [
            'salesAmount' => $salesAmount,
            'salesCount' => $salesCount,
            'period' => $period,
            'periodLabel' => [
                'today' => 'اليوم',
                'yesterday' => 'الأمس',
                'week' => 'هذا الأسبوع',
                'month' => 'هذا الشهر',
                'all' => 'إجمالي المبيعات',
            ][$period],
            'availableStockCount' => $availableStockCount,
            'stockCostValue' => $stockCostValue,
            'currentBudget' => (float) SystemSetting::read('current_budget', 0),
            'customerBalancesTotal' => $customerBalancesTotal,
            'shiftStartedAt' => $shiftStartedAt,
            'currentShift' => $currentShift,
            'lastClosedShift' => $lastClosedShift,
            'categories' => $categories,
            'dailySales' => $dailySales,
            'latestTransactions' => $latestTransactions,
            'automationEnabled' => SystemSetting::read('automation_enabled', false),
        ]);
    }

    public function toggleAutomation(Request $request)
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'period' => ['nullable', Rule::in(['today', 'yesterday', 'week', 'month', 'all'])],
        ]);

        $enabled = (bool) $validated['enabled'];
        SystemSetting::write('automation_enabled', $enabled);

        return redirect()->back()
            ->with('success', $enabled
                ? 'تم تفعيل الروبوت، سوف يقوم الروبوت الآلي بالتقاط الحوالات وتوزيع الكروت للعملاء.'
                : 'تم إيقاف الروبوت الآلي.');
    }
}
