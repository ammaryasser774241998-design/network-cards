<?php

namespace App\Http\Controllers;

use App\Models\BankTransaction;
use App\Models\CardSale;
use App\Models\SystemSetting;
use App\Models\WorkShift;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkShiftController extends Controller
{
    public function index()
    {
        $shifts = WorkShift::with('closer')->latest('closed_at')->paginate(20);

        return view('shifts.index', compact('shifts'));
    }

    public function close(Request $request)
    {
        $shift = DB::transaction(function () use ($request) {
            $startValue = SystemSetting::lock(
                'shift_started_at',
                today()->startOfDay()->toDateTimeString()
            );
            $openedAt = $startValue ? Carbon::parse(json_decode($startValue, true) ?: $startValue) : today()->startOfDay();
            $closedAt = now();

            $salesQuery = CardSale::whereBetween('sold_at', [$openedAt, $closedAt]);
            $transactionsQuery = BankTransaction::whereBetween('created_at', [$openedAt, $closedAt]);
            $shift = WorkShift::create([
                'closed_by' => $request->user()->id,
                'opened_at' => $openedAt,
                'closed_at' => $closedAt,
                'sales_count' => (clone $salesQuery)->count(),
                'sales_amount' => (clone $salesQuery)->sum('amount'),
                'sales_cost' => (clone $salesQuery)->sum('cost_amount'),
                'transactions_count' => (clone $transactionsQuery)->count(),
                'transactions_amount' => (clone $transactionsQuery)->where('status', 'completed')->sum('amount'),
            ]);

            SystemSetting::write('shift_started_at', $closedAt->toDateTimeString());

            return $shift;
        });

        return redirect()
            ->route('dashboard', ['period' => $request->input('period', 'today')])
            ->with('success', "أُقفلت الوردية وحُفظ ملخصها في السجل (رقم {$shift->id}) دون حذف أي بيانات.");
    }
}
