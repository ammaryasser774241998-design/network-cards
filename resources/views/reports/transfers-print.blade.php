<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقرير المالي</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        body {
            color: #111827;
            font-family: Tahoma, Arial, sans-serif;
            font-size: 12px;
            margin: 0;
        }

        h1, h2, p {
            margin: 0;
        }

        .header {
            align-items: center;
            border-bottom: 2px solid #d97706;
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
        }

        .header h1 {
            font-size: 20px;
            margin-bottom: 6px;
        }

        .muted {
            color: #4b5563;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 16px;
        }

        .summary-item {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 8px;
        }

        .summary-item strong {
            display: block;
            font-size: 14px;
            margin-top: 4px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 7px;
            text-align: right;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
        }

        tr {
            break-inside: avoid;
        }

        .print-button {
            background: #d97706;
            border: 0;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            font: inherit;
            margin-bottom: 12px;
            padding: 8px 14px;
        }

        @media print {
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button type="button" class="print-button" onclick="window.print()">طباعة التقرير / حفظ كـ PDF</button>

    <header class="header">
        <div>
            <h1>التقرير المالي</h1>
            <p class="muted">
                الفترة:
                {{ request('from_date') ?: 'بداية السجلات' }}
                —
                {{ request('to_date') ?: 'حتى اليوم' }}
                @if(request('phone')) | الهاتف: {{ request('phone') }} @endif
                | نوع الحركة:
                {{ match(request('type')) { 'sales' => 'مبيعات', 'deposit' => 'إيداع / تحويل', default => 'جميع الحركات' } }}
            </p>
        </div>
        <p class="muted">تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</p>
    </header>

    <section class="summary">
        <div class="summary-item">إجمالي المبيعات<strong>{{ number_format($totalAmount, 2) }} ر.ي</strong></div>
        <div class="summary-item">تكلفة الكروت المباعة<strong>{{ number_format($totalCost, 2) }} ر.ي</strong></div>
        <div class="summary-item">صافي الربح<strong>{{ number_format($netProfit, 2) }} ر.ي</strong></div>
        <div class="summary-item">عدد الكروت المباعة<strong>{{ number_format($soldCardsCount) }}</strong></div>
        <div class="summary-item">المخزون المتبقي<strong>{{ number_format($availableCardsCount) }} كرت</strong></div>
        <div class="summary-item">تكلفة المخزون المتبقي<strong>{{ number_format($remainingStockValue, 2) }} ر.ي</strong></div>
        <div class="summary-item">عمليات مرفوضة / يدوية<strong>{{ number_format($rejectedCount) }}</strong></div>
        <div class="summary-item">عملاء مسجلون في الفترة<strong>{{ number_format($newCustomersCount) }}</strong></div>
        <div class="summary-item">عدد العمليات في التقرير<strong>{{ number_format($transfers->count()) }}</strong></div>
    </section>
    <p style="margin-bottom: 10px;">
        الفئة الأعلى مبيعًا: <strong>{{ $bestSellingCategory->category_name ?? 'لا توجد مبيعات' }}</strong>
        | الفئة الأقل مبيعًا: <strong>{{ $leastSellingCategory->category_name ?? 'لا توجد مبيعات' }}</strong>
    </p>

    <h2 style="font-size: 15px; margin-bottom: 8px;">نتائج العمليات المالية</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>التاريخ</th>
                <th>نوع الحركة</th>
                <th>المبلغ</th>
                <th>العميل والتفاصيل</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transfers as $transfer)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($transfer->operation_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ $transfer->type === 'sales' ? 'بيع كرت' : 'حوالة واردة' }}</td>
                    <td>{{ number_format($transfer->amount, 2) }} ر.ي</td>
                    <td>
                        {{ $transfer->customer_name ?? 'عميل غير معروف' }}
                        @if($transfer->phone) ({{ $transfer->phone }}) @endif
                        — {{ $transfer->description }}
                        @if($transfer->pin_code) — PIN: {{ $transfer->pin_code }} @endif
                        @if($transfer->wallet_name) — {{ $transfer->wallet_name }} @endif
                    </td>
                    <td>{{ match($transfer->status) { 'completed' => 'مكتمل', 'no_stock' => 'لا يوجد مخزون', 'failed' => 'فاشل', default => 'بانتظار المراجعة' } }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">لا توجد عمليات مالية مسجلة في هذه الفترة.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script>
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
