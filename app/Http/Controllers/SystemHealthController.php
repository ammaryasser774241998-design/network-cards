<?php

namespace App\Http\Controllers;

use App\Models\BankTransaction;
use App\Models\SmsOutbox;
use Illuminate\Support\Facades\DB;

class SystemHealthController extends Controller
{
    public function index()
    {
        $since = now()->subDays(30);
        $transactionCounts = BankTransaction::query()
            ->where('created_at', '>=', $since)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
        $smsCounts = SmsOutbox::query()
            ->where('created_at', '>=', $since)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $completed = (int) ($transactionCounts['completed'] ?? 0);
        $terminalFailures = (int) ($transactionCounts['failed'] ?? 0)
            + (int) ($transactionCounts['no_stock'] ?? 0);
        $handled = $completed + $terminalFailures;

        $recentFailures = BankTransaction::with('customer')
            ->whereIn('status', ['failed', 'no_stock'])
            ->latest()
            ->limit(20)
            ->get();

        return view('system-health.index', [
            'transactionCounts' => $transactionCounts,
            'smsCounts' => $smsCounts,
            'successRate' => $handled > 0 ? ($completed / $handled) * 100 : null,
            'recentFailures' => $recentFailures,
            'since' => $since,
        ]);
    }
}
