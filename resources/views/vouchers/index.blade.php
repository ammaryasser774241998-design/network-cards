@extends('layouts.app')

@section('content')
<div class="space-y-6 dir-rtl">
    <div class="rounded-2xl border border-amber-500/30 bg-slate-900 p-5 text-white shadow-md">
        <div class="flex items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-amber-400/30 bg-amber-400/10 text-lg text-amber-400">
                <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
            </span>
            <div class="min-w-0">
                <h2 class="text-xl font-bold text-amber-400">إدارة الكروت والفئات</h2>
                <p class="mt-1 text-sm leading-6 text-slate-300">إضافة الفئات، رفع ملفات الميكروتك/اليوزر مانيجر، واستعراض الكروت المتاحة</p>
            </div>
        </div>
    </div>
        
        <div class="flex flex-wrap gap-4">
            <!-- زر إنشاء فئة جديدة -->
            <button onclick="toggleModal('categoryModal')" class="bg-slate-900 hover:bg-slate-800 text-amber-400 font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2 border border-amber-500/30 shadow-sm">
                <i class="fa-solid fa-folder-plus text-amber-400"></i>
                <span>إنشاء فئة جديدة</span>
            </button>

  {{-- زر إنشاء كرت جديد بناءً على الفئات --}}
    <button onclick="document.getElementById('createCardModal').classList.remove('hidden')" 
            class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-amber-500 to-yellow-600 hover:brightness-110 text-slate-950 font-bold text-sm rounded-lg transition-all shadow-md shadow-amber-500/20">
                        <i class="fa-solid fa-folder-plus text-amber-400"></i>

            <span> إنشاء كرت جديد</span>
    </button>
        

            <!-- زر رفع ملف الكروت -->
            <button onclick="toggleModal('importModal')" class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-amber-500 to-yellow-600 hover:brightness-110 text-slate-950 font-bold text-sm rounded-lg transition-all shadow-md shadow-amber-500/20">

                <i class="fa-solid fa-file-arrow-up"></i>
                <span>رفع ملف الكروت (Excel / PDF)</span>
            </button>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($categories as $category)
                <form action="{{ route('categories.update', $category) }}" method="POST" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    @csrf
                    @method('PUT')
                    <div class="mb-3 flex items-center justify-between">
                        <strong class="text-slate-800">{{ $category->name }}</strong>
                        <span class="text-xs text-slate-500">{{ $category->available_vouchers_count }} متاح</span>
                    </div>
                    <input name="name" value="{{ $category->name }}" required class="mb-2 w-full rounded border border-slate-300 px-3 py-2 text-sm">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <label class="text-xs text-slate-600">سعر البيع<input name="price" type="number" step="0.01" min="0" value="{{ $category->price }}" required class="mt-1 w-full rounded border border-slate-300 px-2 py-2 text-sm"></label>
                        <label class="text-xs text-slate-600">التكلفة<input name="cost_price" type="number" step="0.01" min="0" value="{{ $category->cost_price }}" required class="mt-1 w-full rounded border border-slate-300 px-2 py-2 text-sm"></label>
                        <label class="text-xs text-slate-600">حد التنبيه<input name="low_stock_threshold" type="number" min="0" value="{{ $category->low_stock_threshold }}" required class="mt-1 w-full rounded border border-slate-300 px-2 py-2 text-sm"></label>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <label class="text-xs text-slate-600">سعر الجملة<input name="wholesale_price" type="number" step="0.01" min="0" value="{{ $category->wholesale_price }}" placeholder="اختياري" class="mt-1 w-full rounded border border-slate-300 px-2 py-2 text-sm"></label>
                        <label class="text-xs text-slate-600">أقل كمية للجملة<input name="wholesale_min_quantity" type="number" min="2" max="1000" value="{{ $category->wholesale_min_quantity ?? 2 }}" required class="mt-1 w-full rounded border border-slate-300 px-2 py-2 text-sm"></label>
                        <label class="text-xs text-slate-600">أقل كمية للعروض<input name="bonus_min_quantity" type="number" min="1" max="1000" value="{{ $category->bonus_min_quantity ?? 1 }}" required class="mt-1 w-full rounded border border-slate-300 px-2 py-2 text-sm"></label>
                        <label class="text-xs text-slate-600">كروت مجانية إضافية<input name="bonus_cards" type="number" min="0" max="100" value="{{ $category->bonus_cards ?? 0 }}" required class="mt-1 w-full rounded border border-slate-300 px-2 py-2 text-sm"></label>
                    </div>
                    <button class="mt-3 rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white">حفظ الفئة</button>
                </form>
            @endforeach
        </div>

        <!-- Vouchers Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="flex items-center justify-between gap-3 border-b border-amber-500/20 bg-slate-900 px-4 py-3">
            <h3 class="flex items-center gap-2 font-bold text-amber-400">
                <i class="fa-solid fa-ticket text-sm" aria-hidden="true"></i>
                قائمة الكروت المسجلة
            </h3>
            <span class="rounded-full border border-amber-400/30 bg-slate-800 px-3 py-1 text-xs font-bold text-amber-200">الإجمالي: {{ $vouchers->count() ?? 0 }}</span>
        </div>
        <table class="w-full text-right border-collapse">
            <thead>
                <tr class="bg-slate-900 text-white text-sm">
                    <th class="p-4">#</th>
                    <th class="p-4">كود الكارت (PIN)</th>
                    <th class="p-4">الرقم التسلسلي (Serial)</th>
                    <th class="p-4">الفئة</th>
                    <th class="p-4">سعر التكلفة</th>
                    <th class="p-4">الحالة</th>
                    <th class="p-4">تاريخ الإضافة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($vouchers ?? [] as $voucher)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-4 font-bold">{{ $loop->iteration }}</td>
                        <td class="p-4 font-mono font-bold text-amber-600 text-base">{{ $voucher->pin_code }}</td>
                        <td class="p-4 font-mono text-gray-500">{{ $voucher->serial_number ?? '-' }}</td>
                        <td class="p-4 font-semibold text-slate-800">{{ $voucher->category->name ?? '-' }}</td>
                        <td class="p-4">{{ number_format($voucher->category->cost_price ?? 0, 2) }} ر.ي</td>
                        <td class="p-4">
                            @if($voucher->status == 'available')
                                <span class="bg-emerald-100 text-emerald-800 px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> متاح
                                </span>
                            @else
                                <span class="bg-rose-100 text-rose-800 px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span> مباع
                                </span>
                            @endif
                        </td>
                        <td class="p-4 text-gray-400 dir-ltr text-right">{{ $voucher->created_at ? $voucher->created_at->format('Y-m-d H:i') : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-400">لا توجد كروت مضافة في النظام حالياً.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


<!-- Modal 1: إنشاء فئة جديدة -->
<div id="categoryModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
        <div class="bg-slate-900 text-white p-5 flex justify-between items-center border-b border-amber-500/30">
            <h3 class="font-bold text-lg text-amber-400 flex items-center gap-2">
                <i class="fa-solid fa-folder-plus"></i> إضافة فئة كروت جديدة
            </h3>
            <button onclick="toggleModal('categoryModal')" class="text-gray-400 hover:text-white transition text-xl">&times;</button>
        </div>
        <form action="{{ route('categories.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">اسم الفئة</label>
                <input type="text" name="name" required placeholder="مثال: فئة 500 ريال / 24 ساعة" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">السعر (YER)</label>
                <input type="number" step="0.01" name="price" required placeholder="500" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 outline-none text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">سعر الشراء / التكلفة (YER)</label>
                    <input type="number" step="0.01" min="0" name="cost_price" value="0" required class="w-full p-3 border border-gray-300 rounded-xl text-sm">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">سعر الجملة للوحدة (اختياري)</label>
                        <input type="number" step="0.01" min="0" name="wholesale_price" placeholder="يُطبق عند بلوغ الحد الأدنى" class="w-full p-3 border border-gray-300 rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">الحد الأدنى لسعر الجملة</label>
                        <input type="number" min="2" max="1000" name="wholesale_min_quantity" value="2" required class="w-full p-3 border border-gray-300 rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">الحد الأدنى للكروت المجانية</label>
                        <input type="number" min="1" max="1000" name="bonus_min_quantity" value="1" required class="w-full p-3 border border-gray-300 rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">عدد الكروت المجانية الإضافية</label>
                        <input type="number" min="0" max="100" name="bonus_cards" value="0" required class="w-full p-3 border border-gray-300 rounded-xl text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">حد تنبيه المخزون</label>
                    <input type="number" min="0" name="low_stock_threshold" value="5" required class="w-full p-3 border border-gray-300 rounded-xl text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">الوصف (اختياري)</label>
                <textarea name="description" rows="2" placeholder="تفاصيل صلاحية الفئة أو السرعة..." class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 outline-none text-sm"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-3 border-t">
                <button type="button" onclick="toggleModal('categoryModal')" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-slate-700 font-bold rounded-xl text-sm transition">إلغاء</button>
                <button type="submit" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold rounded-xl text-sm transition shadow-md">حفظ الفئة</button>
            </div>
        </form>
    </div>
</div>

 

    {{-- نافذة (Modal) إنشاء كرت حسب الفئة --}}
<div id="createCardModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-amber-500/30 rounded-xl w-full max-w-lg shadow-2xl overflow-hidden">
        
        {{-- رأس النافذة --}}
        <div class="p-4 bg-slate-800/80 border-b border-amber-500/20 flex items-center justify-between">
            <h3 class="text-base font-bold text-amber-400">إضافة كرت جديد حسب الفئة</h3>
            <button onclick="document.getElementById('createCardModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-200">✕</button>
        </div>

        {{-- نموذج إدخال الكرت --}}
        <form action="{{ route('vouchers.store') }}" method="POST" class="p-5 space-y-4">
            @csrf

            {{-- اختيار الفئة المتواجدة بالنظام --}}
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">اختر الفئة *</label>
                <select name="category_id" required class="w-full bg-slate-800 border border-slate-700 text-slate-100 rounded-lg px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
                    <option value="" disabled selected>-- اختر الفئة المتاحة --</option>
                    @foreach($categories ?? [] as $category)
                        <option value="{{ $category->id }}">
                            {{ $category->name }} (السعر: {{ $category->price }} ر.ي)
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- رقم الكارت / السيريال --}}
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">رقم الكارت / السيريال *</label>
                <input type="text" name="card_number" required placeholder="أدخل رقم الكارت" class="w-full bg-slate-800 border border-slate-700 text-slate-100 rounded-lg px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
            </div>

            {{-- كلمة السر / PIN (اختياري) --}}
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">رمز PIN / كلمة السر</label>
                <input type="text" name="pin_code" placeholder="أدخل رمز PIN إن وجد" class="w-full bg-slate-800 border border-slate-700 text-slate-100 rounded-lg px-3 py-2 text-sm focus:border-amber-500 focus:outline-none">
            </div>

            {{-- أزرار الإجراءات --}}
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('createCardModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs rounded-lg transition-all">
                    إلغاء
                </button>
                <button type="submit" class="px-5 py-2 bg-gradient-to-r from-amber-500 to-yellow-600 hover:brightness-110 text-slate-950 font-bold text-xs rounded-lg transition-all shadow-md shadow-amber-500/20">
                    حفظ الكارت
                </button>
            </div>
        </form>

    </div>
</div>

<!-- Modal 2: رفع ملف الكروت (User Manager PDF / Excel) -->
<div id="importModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl border border-slate-100 overflow-hidden">
        <div class="bg-slate-900 text-white p-5 flex justify-between items-center border-b border-amber-500/30">
            <h3 class="font-bold text-lg text-amber-400 flex items-center gap-2">
                <i class="fa-solid fa-file-arrow-up"></i> رفع كروت من اليوزر مانيجر
            </h3>
            <button onclick="toggleModal('importModal')" class="text-gray-400 hover:text-white transition text-xl">&times;</button>
        </div>
        <form action="{{ route('vouchers.import') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">تحديد الفئة</label>
                <select name="category_id" required class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 outline-none text-sm bg-white">
                    <option value="" disabled selected>اختر الفئة المخصصة لملف الكروت...</option>
                    @foreach($categories ?? [] as $category)
                        <option value="{{ $category->id }}">{{ $category->name }} ({{ number_format($category->price, 0) }} YER)</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">اختر ملف الكروت (PDF أو Excel)</label>
                <div class="border-2 border-dashed border-gray-300 rounded-2xl p-6 text-center hover:border-amber-500 transition bg-slate-50/50">
                    <i class="fa-solid fa-cloud-arrow-up text-4xl text-amber-500 mb-2"></i>
                    <p class="text-xs text-gray-600 mb-2 font-medium">الصيغ المدعومة: <span class="font-bold text-slate-800">PDF, XLSX, XLS, CSV</span></p>
                    <input type="file" name="file" accept=".pdf,.xlsx,.xls,.csv,.txt" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-900 file:text-amber-400 hover:file:bg-slate-800 cursor-pointer">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">أو الصق الأكواد مباشرة</label>
                <textarea name="codes" rows="4" placeholder="كل سطر: PIN, Serial (يمكن ترك الرقم التسلسلي فارغاً)" class="w-full rounded-xl border border-gray-300 p-3 text-sm"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t">
                <button type="button" onclick="toggleModal('importModal')" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-slate-700 font-bold rounded-xl text-sm transition">إلغاء</button>
                <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-amber-400 font-bold rounded-xl text-sm transition border border-amber-500/30 shadow-md">بدء المعالجة والرفع</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.toggle('hidden');
        }
    }
</script>
@endsection