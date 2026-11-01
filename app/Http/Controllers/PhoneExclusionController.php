<?php

namespace App\Http\Controllers;

use App\Models\PhoneExclusion;
use App\Services\PhoneExclusionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PhoneExclusionController extends Controller
{
    public function index()
    {
        $exclusions = PhoneExclusion::latest()->paginate(25);

        return view('exclusions.index', compact('exclusions'));
    }

    public function store(Request $request, PhoneExclusionService $phoneExclusions)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string|max:30',
            'reason' => 'nullable|string|max:255',
        ]);
        $phone = $phoneExclusions->normalize($validated['phone_number']);
        if ($phone === '') {
            return back()->withErrors(['phone_number' => 'أدخل رقم هاتف صالحاً.'])->withInput();
        }
        if (PhoneExclusion::where('phone_number', $phone)->exists()) {
            return back()->withErrors(['phone_number' => 'هذا الرقم موجود مسبقًا في قائمة الاستثناءات.'])->withInput();
        }

        PhoneExclusion::create([
            'phone_number' => $phone,
            'reason' => $validated['reason'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'تمت إضافة الرقم إلى قائمة الاستثناءات؛ ستظل حوالاته مسجلة للمراجعة.');
    }

    public function destroy(PhoneExclusion $phoneExclusion)
    {
        $phoneExclusion->delete();

        return back()->with('success', 'تمت إزالة الرقم من قائمة الاستثناءات.');
    }
}
