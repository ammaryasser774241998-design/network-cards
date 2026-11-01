<?php

namespace App\Http\Controllers;

use App\Models\CardCategory;
use App\Models\Voucher;
use App\Services\InventoryAlertService;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class VoucherController extends Controller
{
    public function store(Request $request, InventoryAlertService $inventoryAlerts)
    {
        $validated = $request->validate([
            'category_id' => [
                'required',
                Rule::exists('card_categories', 'id')
                    ->where(fn ($query) => TenantContext::scopeQuery($query, 'tenant_id')),
            ],
            'card_number' => 'required|string|max:255',
            'pin_code' => 'nullable|string|max:255',
        ]);

        $voucher = Voucher::create([
            'category_id' => $validated['category_id'],
            'serial_number' => $validated['card_number'],
            'pin_code' => $validated['pin_code'] ?: $validated['card_number'],
            'status' => 'available',
        ]);

        $inventoryAlerts->check($voucher->category);

        return back()->with('success', 'تمت إضافة الكرت بنجاح.');
    }

    public function import(Request $request, InventoryAlertService $inventoryAlerts)
    {
        $validated = $request->validate([
            'category_id' => [
                'required',
                Rule::exists('card_categories', 'id')
                    ->where(fn ($query) => TenantContext::scopeQuery($query, 'tenant_id')),
            ],
            'file' => 'nullable|required_without:codes|file|mimes:pdf,xlsx,xls,csv,txt|max:10240',
            'codes' => 'nullable|required_without:file|string|max:1000000',
        ]);

        $category = CardCategory::findOrFail($validated['category_id']);
        $rows = isset($validated['codes'])
            ? $this->parseTextRows($validated['codes'])
            : $this->parseFileRows($request->file('file'));

        $imported = DB::transaction(function () use ($rows, $category) {
            $count = 0;
            foreach ($rows as [$pinCode, $serialNumber]) {
                if ($pinCode === '') {
                    continue;
                }

                $voucher = Voucher::firstOrCreate(
                    ['pin_code' => $pinCode],
                    [
                        'category_id' => $category->id,
                        'serial_number' => $serialNumber ?: null,
                        'status' => 'available',
                    ]
                );
                $count += $voucher->wasRecentlyCreated ? 1 : 0;
            }

            return $count;
        });

        if ($imported === 0) {
            return back()->with('error', 'لم يتم استيراد أي كرت جديد. تحقق من محتوى الملف أو أن الأكواد غير مكررة.');
        }

        $inventoryAlerts->check($category);

        return back()->with('success', "تم استيراد {$imported} كرت بنجاح.");
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:2000',
            'wholesale_price' => 'nullable|numeric|min:0|lt:price',
            'wholesale_min_quantity' => 'nullable|integer|min:2|max:1000',
            'bonus_min_quantity' => 'nullable|integer|min:1|max:1000',
            'bonus_cards' => 'nullable|integer|min:0|max:100',
        ]);

        CardCategory::create([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'cost_price' => $validated['cost_price'] ?? 0,
            'low_stock_threshold' => $validated['low_stock_threshold'] ?? 5,
            'description' => $validated['description'] ?? null,
            'wholesale_price' => $validated['wholesale_price'] ?? null,
            'wholesale_min_quantity' => $validated['wholesale_min_quantity'] ?? 2,
            'bonus_min_quantity' => $validated['bonus_min_quantity'] ?? 1,
            'bonus_cards' => $validated['bonus_cards'] ?? 0,
        ]);

        return back()->with('success', 'تم إنشاء الفئة بنجاح.');
    }

    public function destroyCategory($id)
    {
        $category = CardCategory::findOrFail($id);
        $category->delete();

        return back()->with('success', 'تم حذف الفئة والكروت المرتبطة بها.');
    }

    public function index()
    {
        $categories = CardCategory::withCount([
            'vouchers',
            'vouchers as available_vouchers_count' => fn ($query) => $query->where('status', 'available'),
        ])->get();
        $vouchers = Voucher::with(['category', 'customer'])->latest()->paginate(15);

        return view('vouchers.index', compact('categories', 'vouchers'));
    }

    private function parseFileRows($file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension === 'pdf') {
            $text = (new PdfParser)->parseFile($file->getRealPath())->getText();

            return $this->parseTextRows($text);
        }

        if (in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
            $rows = [];

            for ($row = 1; $row <= $sheet->getHighestDataRow(); $row++) {
                $pin = trim((string) $sheet->getCell("A{$row}")->getFormattedValue());
                $serial = trim((string) $sheet->getCell("B{$row}")->getFormattedValue());
                if ($row === 1 && in_array(mb_strtolower($pin), ['pin', 'code', 'pin_code', 'كود'], true)) {
                    continue;
                }
                $rows[] = [$pin, $serial];
            }

            return $rows;
        }

        return $this->parseTextRows(file_get_contents($file->getRealPath()));
    }

    private function parseTextRows(string $text): array
    {
        $rows = [];
        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = preg_split('/[,;\t|]+|\s{2,}/u', $line, 2);
            $pin = trim($parts[0] ?? '');
            $serial = trim($parts[1] ?? '');

            if ($pin !== '') {
                $rows[] = [$pin, $serial];
            }
        }

        return $rows;
    }
}
