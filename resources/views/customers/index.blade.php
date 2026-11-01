@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary-navy: #0f172a;
        --secondary-navy: #1e293b;
        --accent-gold: #d4af37;
        --accent-gold-hover: #b89628;
        --bg-light: #f8fafc;
        --text-dark: #0f172a;
        --border-color: #cbd5e1;
    }

    body {
        background-color: var(--bg-light);
        color: var(--text-dark);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* هيدر الصفحة */
    .page-header {
        background: linear-gradient(135deg, var(--primary-navy) 0%, var(--secondary-navy) 100%);
        border-bottom: 3px solid var(--accent-gold);
        color: #ffffff;
        border-radius: 10px;
        padding: 18px 25px;
        margin-bottom: 20px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
    }

    .page-title {
        color: var(--accent-gold);
        font-weight: 700;
        font-size: 1.5rem;
        margin: 0;
    }

    /* الكروت والجداول */
    .custom-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        margin-bottom: 22px;
        overflow: hidden;
    }

    .card-header-custom {
        background-color: var(--primary-navy);
        color: #ffffff;
        padding: 14px 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 2px solid var(--accent-gold);
    }

    .card-header-custom h5 {
        margin: 0;
        font-size: 1.05rem;
        color: #ffffff;
    }

    .table-custom {
        width: 100%;
        margin-bottom: 0;
        border-collapse: collapse;
    }

    .table-custom th {
        background-color: #f1f5f9;
        color: var(--primary-navy);
        font-weight: 700;
        border-bottom: 2px solid var(--border-color);
        padding: 12px 10px;
        text-align: right;
        white-space: nowrap;
    }

    .table-custom td {
        padding: 10px;
        vertical-align: middle;
        border-bottom: 1px solid #e2e8f0;
    }

    .table-custom tbody tr:hover {
        background-color: #f8fafc;
    }

    /* مدخلات الإضافة */
    .form-control-custom {
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 6px 10px;
        width: 100%;
        font-size: 0.9rem;
        color: var(--text-dark);
        background-color: #ffffff;
    }

    .form-control-custom:focus {
        border-color: var(--accent-gold);
        outline: none;
        box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.2);
    }

    /* تنسيق مربع البحث لتوضيح الخط */
    #searchCustomer {
        color: #0f172a !important;
        background-color: #ffffff !important;
        border: 1px solid var(--border-color) !important;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.92rem;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
    }

    #searchCustomer::placeholder {
        color: #94a3b8 !important;
    }

    /* تظليل النص المطابق للبحث */
    .search-highlight {
        background-color: #fef08a !important;
        color: #854d0e !important;
        font-weight: bold;
        padding: 1px 3px;
        border-radius: 3px;
    }

    /* الأزرار والشارات */
    .btn-gold {
        background-color: var(--accent-gold);
        color: var(--primary-navy);
        font-weight: 700;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .btn-gold:hover {
        background-color: var(--accent-gold-hover);
        color: #ffffff;
    }

    .btn-navy {
        background-color: var(--primary-navy);
        color: var(--accent-gold);
        border: 1px solid var(--accent-gold);
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 5px;
    }

    .btn-navy:hover {
        background-color: var(--secondary-navy);
        color: #ffffff;
    }

    .table-responsive {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-x: contain;
        touch-action: pan-x;
    }

    .table-responsive > table {
        width: 100%;
        min-width: 950px;
        max-width: none;
    }

    @media (max-width: 767px) {
        .table-responsive > table {
            display: table;
            width: 950px;
            min-width: 950px;
            max-width: none;
            overflow: visible;
        }
    }

    @media (max-width: 640px) {
        .page-header,
        .card-header-custom {
            align-items: stretch;
            flex-direction: column;
            gap: 12px;
            padding: 14px;
        }

        .page-title {
            font-size: 1.2rem;
        }

        #searchCustomer {
            width: 100% !important;
        }

        .card-header-custom form {
            width: 100%;
        }

    }
</style>

<div class="container-fluid dir-rtl" dir="rtl">
    
    <!-- الهيدر العلوي -->
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-users-cog me-2"></i> إدارة العملاء</h1>
            <small class="text-light">لوحة التحكم الخاصة بإضافة ومتابعة عملاء النظام</small>
        </div>
        <div>
            <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                إجمالي العملاء: {{ number_format($totalCustomers ?? 0) }}
            </span>
        </div>
    </div>

    <!-- القسم 1: نموذج إضافة عميل جديد -->
    <div class="custom-card">
        <div class="card-header-custom">
            <h5><i class="fas fa-user-plus me-2"></i> إضافة عميل جديد إلى النظام</h5>
        </div>
        <div class="card-body p-0">
            <form action="{{ route('customers.store') }}" method="POST">
                @csrf
                <div class="table-responsive">
                    <table class="table table-custom align-middle m-0">
                        <thead>
                            <tr>
                                <th style="width: 22%;">اسم العميل <span class="text-danger">*</span></th>
                                <th style="width: 18%;">رقم الهاتف <span class="text-danger">*</span></th>
                                <th style="width: 22%;">الحساب البنكي (المودع)</th>
                                <th style="width: 15%;">الرصيد الأولي / الدين</th>
                                <th style="width: 15%;">تاريخ التسجيل</th>
                                <th style="width: 13%;">الحالة</th>
                                <th style="width: 10%; text-align: center;">إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <input type="text" name="full_name" class="form-control-custom" placeholder="أدخل اسم العميل الكامل" required>
                                </td>
                                <td>
                                    <input type="text" name="phone_number" class="form-control-custom" placeholder="77xxxxxxxx" required>
                                </td>
                                <td>
                                    <input type="text" name="bank_account_name" class="form-control-custom" placeholder="رقم الحساب أو اسم المودع">
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="balance" class="form-control-custom" placeholder="0.00">
                                </td>
                                 <td>
                                    <input type="date" name="registration_date" class="form-control-custom" value="{{ date('Y-m-d') }}">
                                 </td>
                                <td>
                                    <select name="status" class="form-control-custom">
                                        <option value="active" selected>نشط</option>
                                        <option value="inactive">غير نشط</option>
                                    </select>
                                </td>
                                <td style="text-align: center;">
                                    <button type="submit" class="btn btn-gold w-100">
                                        <i class="fas fa-plus-circle me-1"></i> حفظ
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

    <!-- القسم 2: قائمة العملاء المسجلين + مربع البحث -->
    <div class="custom-card">
        <div class="card-header-custom">
            <h5><i class="fas fa-list-alt me-2"></i> قائمة العملاء المسجلين</h5>
            <form method="GET" action="{{ route('customers.index') }}">
                <input type="text" id="searchCustomer" name="search" value="{{ request('search') }}" class="form-control" placeholder="بحث باسم العميل، الهاتف أو الحساب..." style="width: 280px;">
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th style="width: 4%;">#</th>
                            <th style="width: 22%;">اسم العميل</th>
                            <th style="width: 16%;">رقم الهاتف</th>
                            <th style="width: 20%;">الحساب البنكي (المودع)</th>
                            <th style="width: 12%;">الرصيد الحالي</th>
                            <th style="width: 14%;">تاريخ التسجيل</th>
                            <th style="width: 6%;">الحالة</th>
                            <th style="width: 6%; text-align: center;">العمليات</th>
                        </tr>
                    </thead>
                    <tbody id="customersTableBody">
                        @forelse($customers ?? [] as $customer)
                            <tr>
                                <td>{{ $customers->firstItem() + $loop->index }}</td>
                                <td class="fw-bold text-dark searchable">{{ $customer->full_name }}</td>
                                <td class="searchable">{{ $customer->phone_number }}</td>
                                <td class="searchable">{{ $customer->bank_account_name ?? 'غير محدد' }}</td>
                                <td class="fw-bold {{ ($customer->balance ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($customer->balance ?? 0, 2) }}
                                    <div class="text-xs text-slate-500">{{ $customer->sales_count }} عملية بيع</div>
                                </td>
                                <td>{{ $customer->created_at ? $customer->created_at->format('Y-m-d') : '-' }}</td>

                                <td>
                                    @if(($customer->status ?? 'active') === 'active')
                                        <span class="badge bg-success">نشط</span>
                                    @else
                                        <span class="badge bg-danger">غير نشط</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" onclick="document.getElementById('edit-customer-{{ $customer->id }}').classList.remove('hidden')" class="btn btn-sm btn-navy" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" data-confirm-delete="هل أنت متأكد من حذف هذا العميل؟ ستبقى سجلاته المالية السابقة محفوظة." class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <div id="edit-customer-{{ $customer->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/70 p-4">
                                        <form action="{{ route('customers.update', $customer) }}" method="POST" class="w-full max-w-lg space-y-3 rounded-xl bg-white p-6 text-right shadow-xl">
                                            @csrf
                                            @method('PUT')
                                            <h3 class="font-bold">تعديل بيانات {{ $customer->full_name }}</h3>
                                            <input name="full_name" value="{{ $customer->full_name }}" required class="form-control-custom">
                                            <input name="phone_number" value="{{ $customer->phone_number }}" required class="form-control-custom" dir="ltr">
                                            <input name="bank_account_name" value="{{ $customer->bank_account_name }}" class="form-control-custom" placeholder="اسم الحساب">
                                            <input name="bank_account_number" value="{{ $customer->bank_account_number }}" class="form-control-custom" placeholder="رقم الحساب">
                                            <input name="balance" type="number" step="0.01" value="{{ $customer->balance }}" class="form-control-custom" placeholder="الرصيد">
                                            <select name="status" class="form-control-custom">
                                                <option value="active" @selected($customer->status === 'active')>نشط</option>
                                                <option value="inactive" @selected($customer->status === 'inactive')>غير نشط</option>
                                            </select>
                                            <div class="flex justify-end gap-2">
                                                <button type="button" onclick="document.getElementById('edit-customer-{{ $customer->id }}').classList.add('hidden')" class="btn btn-navy">إلغاء</button>
                                                <button class="btn btn-gold">حفظ</button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    لا يوجد عملاء مسجلون حالياً في النظام.
                                </td>
                            </tr>
                        @endforelse

                        <!-- صف رسالة الاعتذار عند عدم مطابقة نتائج البحث -->
                        <tr id="noResultsRow" style="display: none;">
                            <td colspan="8" class="text-center py-4 text-danger fw-bold">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                عذراً، لم يتم الحصول على عميل بهذه المعلومات.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="p-3">{{ $customers->links() }}</div>
        </div>
    </div>

</div>

<!-- سكريبت البحث الفوري والتظليل المباشر -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchCustomer');
    const tableBody = document.getElementById('customersTableBody');
    if (!searchInput || !tableBody) return;

    const dataRows = tableBody.querySelectorAll('tr:not(#noResultsRow)');
    const noResultsRow = document.getElementById('noResultsRow');

    // حفظ النص الأصلي لكل خلية قابلة للبحث
    dataRows.forEach(row => {
        row.querySelectorAll('.searchable').forEach(cell => {
            cell.setAttribute('data-original', cell.textContent.trim());
        });
    });

    function applySearch() {
        const searchValue = searchInput.value.trim();
        const query = searchValue.toLocaleLowerCase();
        const terms = query.split(/\s+/).filter(Boolean);
        const phoneQuery = /^\+?[\d\s().-]+$/.test(searchValue)
            ? searchValue.replace(/\D/g, '')
            : '';
        let visibleCount = 0;

        dataRows.forEach(row => {
            const cells = row.querySelectorAll('.searchable');
            const cellTexts = Array.from(cells, cell => cell.getAttribute('data-original'));
            const textMatch = terms.length > 0 && terms.every(term =>
                cellTexts.some(text => text.toLocaleLowerCase().includes(term))
            );
            const phoneMatch = phoneQuery.length > 0 && cellTexts.some(text =>
                text.replace(/\D/g, '').includes(phoneQuery)
            );
            const isMatch = query === '' || textMatch || phoneMatch;

            cells.forEach(cell => {
                const originalText = cell.getAttribute('data-original');
                const ranges = [];
                const lowerText = originalText.toLocaleLowerCase();

                if (query !== '') {
                    terms.forEach(term => {
                        let start = 0;
                        let matchIndex;
                        while ((matchIndex = lowerText.indexOf(term, start)) !== -1) {
                            ranges.push([matchIndex, matchIndex + term.length]);
                            start = matchIndex + term.length;
                        }
                    });

                    const normalizedPhone = originalText.replace(/\D/g, '');
                    const phoneStart = normalizedPhone.indexOf(phoneQuery);
                    if (phoneQuery && phoneStart !== -1) {
                        let digitIndex = 0;
                        let start = null;
                        let previous = null;
                        for (let characterIndex = 0; characterIndex < originalText.length; characterIndex++) {
                            if (/\d/.test(originalText[characterIndex])) {
                                if (digitIndex >= phoneStart
                                    && digitIndex < phoneStart + phoneQuery.length) {
                                    if (start === null) start = characterIndex;
                                    if (previous !== null && characterIndex > previous + 1) {
                                        ranges.push([start, previous + 1]);
                                        start = characterIndex;
                                    }
                                    previous = characterIndex;
                                }
                                digitIndex++;
                            } else if (start !== null && previous !== null && characterIndex > previous + 1) {
                                ranges.push([start, previous + 1]);
                                start = null;
                                previous = null;
                            }
                        }
                        if (start !== null && previous !== null) ranges.push([start, previous + 1]);
                    }
                }

                const mergedRanges = ranges
                    .sort((first, second) => first[0] - second[0] || first[1] - second[1])
                    .reduce((merged, range) => {
                        const previous = merged[merged.length - 1];
                        if (previous && range[0] <= previous[1]) {
                            previous[1] = Math.max(previous[1], range[1]);
                        } else {
                            merged.push([...range]);
                        }
                        return merged;
                    }, []);

                cell.replaceChildren();
                let textOffset = 0;
                mergedRanges.forEach(([start, end]) => {
                    cell.append(document.createTextNode(originalText.slice(textOffset, start)));
                    const mark = document.createElement('mark');
                    mark.className = 'search-highlight';
                    mark.textContent = originalText.slice(start, end);
                    cell.append(mark);
                    textOffset = end;
                });
                cell.append(document.createTextNode(originalText.slice(textOffset)));
            });

            row.style.display = isMatch ? '' : 'none';
            if (isMatch) visibleCount++;
        });

        if (noResultsRow) {
            noResultsRow.style.display = (visibleCount === 0 && query !== '') ? '' : 'none';
        }
    }

    searchInput.addEventListener('input', applySearch);
    applySearch();
});
</script>
@endsection