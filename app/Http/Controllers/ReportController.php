<?php

namespace App\Http\Controllers;

use App\Models\BankTransaction;
use App\Models\CardSale;
use App\Models\Customer;
use App\Models\Voucher;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function transfers(Request $request)
    {
        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'type' => 'nullable|in:sales,deposit',
            'phone' => 'nullable|string|max:30',
            'print' => 'sometimes|boolean',
        ]);

        $sales = TenantContext::scopeQuery(DB::table('card_sales'), 'card_sales.tenant_id')
            ->leftJoin('customers', function ($join) {
                $join->on('card_sales.customer_id', '=', 'customers.id')
                    ->whereRaw('(customers.tenant_id = card_sales.tenant_id OR (customers.tenant_id IS NULL AND card_sales.tenant_id IS NULL))');
            })
            ->selectRaw("'sales' as type, card_sales.id, sold_at as operation_at, customer_phone as phone, COALESCE(customers.full_name, customer_phone) as customer_name, category_name as description, amount, pin_code, wallet_name, source as channel, 'completed' as status");
        $deposits = TenantContext::scopeQuery(DB::table('bank_transactions'), 'bank_transactions.tenant_id')
            ->leftJoin('customers', function ($join) {
                $join->on('bank_transactions.customer_id', '=', 'customers.id')
                    ->whereRaw('(customers.tenant_id = bank_transactions.tenant_id OR (customers.tenant_id IS NULL AND bank_transactions.tenant_id IS NULL))');
            })
            ->selectRaw("'deposit' as type, bank_transactions.id, bank_transactions.created_at as operation_at, sender_phone as phone, COALESCE(customers.full_name, sender_name_raw, sender_phone) as customer_name, COALESCE(processing_note, raw_message) as description, amount, NULL as pin_code, wallet_name, source_device as channel, bank_transactions.status");

        if (! empty($validated['from_date'])) {
            $sales->whereDate('sold_at', '>=', $validated['from_date']);
            $deposits->whereDate('bank_transactions.created_at', '>=', $validated['from_date']);
        }
        if (! empty($validated['to_date'])) {
            $sales->whereDate('sold_at', '<=', $validated['to_date']);
            $deposits->whereDate('bank_transactions.created_at', '<=', $validated['to_date']);
        }
        if (! empty($validated['phone'])) {
            $search = $validated['phone'];
            $phoneSearch = preg_match('/^[+\d\s().-]+$/u', $search)
                ? (string) preg_replace('/\D+/', '', $search)
                : '';
            $sales->where(fn ($query) => $query
                ->where('card_sales.customer_phone', 'like', '%'.$search.'%')
                ->when($phoneSearch !== '' && $phoneSearch !== $search, fn ($query) => $query
                    ->orWhere('card_sales.customer_phone', 'like', '%'.$phoneSearch.'%'))
                ->orWhere('customers.phone_number', 'like', '%'.$search.'%')
                ->when($phoneSearch !== '' && $phoneSearch !== $search, fn ($query) => $query
                    ->orWhere('customers.phone_number', 'like', '%'.$phoneSearch.'%'))
                ->orWhere('customers.full_name', 'like', '%'.$search.'%'));
            $deposits->where(fn ($query) => $query
                ->where('bank_transactions.sender_phone', 'like', '%'.$search.'%')
                ->when($phoneSearch !== '' && $phoneSearch !== $search, fn ($query) => $query
                    ->orWhere('bank_transactions.sender_phone', 'like', '%'.$phoneSearch.'%'))
                ->orWhere('customers.phone_number', 'like', '%'.$search.'%')
                ->when($phoneSearch !== '' && $phoneSearch !== $search, fn ($query) => $query
                    ->orWhere('customers.phone_number', 'like', '%'.$phoneSearch.'%'))
                ->orWhere('customers.full_name', 'like', '%'.$search.'%')
                ->orWhere('bank_transactions.sender_name_raw', 'like', '%'.$search.'%'));
        }

        $type = $validated['type'] ?? null;
        if ($type === 'sales') {
            $operations = $sales;
        } elseif ($type === 'deposit') {
            $operations = $deposits;
        } else {
            $operations = $sales->unionAll($deposits);
        }

        $operationsQuery = DB::query()
            ->fromSub($operations, 'operations')
            ->orderByDesc('operation_at')
            ->orderByDesc('id');
        $transfers = $request->boolean('print')
            ? $operationsQuery->get()
            : $operationsQuery->paginate(15)->withQueryString();

        $salesQuery = CardSale::query();
        if (! empty($validated['from_date'])) {
            $salesQuery->whereDate('sold_at', '>=', $validated['from_date']);
        }
        if (! empty($validated['to_date'])) {
            $salesQuery->whereDate('sold_at', '<=', $validated['to_date']);
        }
        if (! empty($validated['phone'])) {
            $search = $validated['phone'];
            $phoneSearch = preg_match('/^[+\d\s().-]+$/u', $search)
                ? (string) preg_replace('/\D+/', '', $search)
                : '';
            $salesQuery->where(fn ($query) => $query
                ->where('customer_phone', 'like', '%'.$search.'%')
                ->when($phoneSearch !== '' && $phoneSearch !== $search, fn ($query) => $query
                    ->orWhere('customer_phone', 'like', '%'.$phoneSearch.'%'))
                ->orWhereHas('customer', fn ($customerQuery) => $customerQuery
                    ->where('phone_number', 'like', '%'.$search.'%')
                    ->when($phoneSearch !== '' && $phoneSearch !== $search, fn ($query) => $query
                        ->orWhere('phone_number', 'like', '%'.$phoneSearch.'%'))
                    ->orWhere('full_name', 'like', '%'.$search.'%')));
        }

        $totalAmount = (clone $salesQuery)->sum('amount');
        $totalCost = (clone $salesQuery)->sum('cost_amount');
        $soldCardsCount = (clone $salesQuery)->count();
        $netProfit = $totalAmount - $totalCost;
        $availableCardsCount = Voucher::where('status', 'available')->count();
        $remainingStockValue = Voucher::query()
            ->join('card_categories', 'vouchers.category_id', '=', 'card_categories.id')
            ->whereRaw('(card_categories.tenant_id = vouchers.tenant_id OR (card_categories.tenant_id IS NULL AND vouchers.tenant_id IS NULL))')
            ->where('vouchers.status', 'available')
            ->sum('card_categories.cost_price');
        $rejectedCount = BankTransaction::whereIn('status', ['failed', 'no_stock', 'pending_customer'])
            ->when(! empty($validated['from_date']), fn ($query) => $query->whereDate('created_at', '>=', $validated['from_date']))
            ->when(! empty($validated['to_date']), fn ($query) => $query->whereDate('created_at', '<=', $validated['to_date']))
            ->when(! empty($validated['phone']), fn ($query) => $query->where(function ($query) use ($validated) {
                $search = $validated['phone'];
                $query->where('sender_phone', 'like', '%'.$search.'%')
                    ->orWhere('sender_name_raw', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', fn ($customerQuery) => $customerQuery
                        ->where('phone_number', 'like', '%'.$search.'%')
                        ->orWhere('full_name', 'like', '%'.$search.'%'));
            }))
            ->count();
        $newCustomersCount = Customer::query()
            ->where('is_guest', false)
            ->when(! empty($validated['from_date']), fn ($query) => $query->whereDate('created_at', '>=', $validated['from_date']))
            ->when(! empty($validated['to_date']), fn ($query) => $query->whereDate('created_at', '<=', $validated['to_date']))
            ->when(! empty($validated['phone']), fn ($query) => $query->where(function ($query) use ($validated) {
                $search = $validated['phone'];
                $phoneSearch = preg_match('/^[+\d\s().-]+$/u', $search)
                    ? (string) preg_replace('/\D+/', '', $search)
                    : '';
                $query->where('phone_number', 'like', '%'.$search.'%')
                    ->when($phoneSearch !== '' && $phoneSearch !== $search, fn ($query) => $query
                        ->orWhere('phone_number', 'like', '%'.$phoneSearch.'%'))
                    ->orWhere('full_name', 'like', '%'.$search.'%');
            }))
            ->count();
        $categorySales = (clone $salesQuery)
            ->selectRaw('category_name, SUM(amount) as total_amount, COUNT(*) as cards_count')
            ->groupBy('category_name')
            ->orderByDesc('cards_count')
            ->orderByDesc('total_amount')
            ->orderBy('category_name')
            ->get();
        $bestSellingCategory = $categorySales->first();
        $leastSellingCategory = $categorySales
            ->sortBy([
                ['cards_count', 'asc'],
                ['total_amount', 'asc'],
                ['category_name', 'asc'],
            ])
            ->first();

        $reportData = compact(
            'transfers',
            'totalAmount',
            'totalCost',
            'netProfit',
            'soldCardsCount',
            'availableCardsCount',
            'remainingStockValue',
            'rejectedCount',
            'newCustomersCount',
            'bestSellingCategory',
            'leastSellingCategory',
        );

        return $request->boolean('print')
            ? view('reports.transfers-print', $reportData)
            : view('reports.transfers', $reportData);
    }
}
