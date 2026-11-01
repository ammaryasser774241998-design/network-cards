<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SellerController extends Controller
{
    public function index()
    {
        $sellers = User::query()
            ->with('tenant')
            ->where('account_type', 'seller')
            ->orderBy('name')
            ->get();

        return view('sellers.index', compact('sellers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:30', 'unique:users,phone_number'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        DB::transaction(function () use ($validated) {
            $tenant = Tenant::create(['name' => $validated['name'].' — شبكة البائع']);
            $seller = new User;
            $seller->forceFill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'password' => Hash::make($validated['password']),
                'tenant_id' => $tenant->id,
                'account_type' => 'seller',
                'is_admin' => false,
                'is_active' => true,
            ]);
            $seller->save();
        });

        return back()->with('success', 'تم إنشاء حساب البائع ومساحة بياناته بنجاح.');
    }

    public function updateStatus(Request $request, User $user)
    {
        abort_unless($user->account_type === 'seller', 404);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user->update(['is_active' => (bool) $validated['is_active']]);

        return back()->with('success', $user->is_active
            ? 'تم تفعيل حساب البائع.'
            : 'تم إيقاف حساب البائع.');
    }
}
