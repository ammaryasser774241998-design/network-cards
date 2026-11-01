<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanionContactsController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'contacts' => 'required|array|max:1000',
            'contacts.*.name' => 'required|string|max:255',
            'contacts.*.phone' => 'required|string|max:30',
        ]);

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($validated, &$created, &$updated) {
            foreach ($validated['contacts'] as $contact) {
                $phone = preg_replace('/\D+/', '', $contact['phone']);
                if ($phone === '') {
                    continue;
                }

                $suffix = substr($phone, -9);
                $matches = Customer::query()
                    ->where(fn ($query) => $query
                        ->where('phone_number', $phone)
                        ->orWhere('phone_number', 'LIKE', '%'.$suffix)
                        ->orWhereJsonContains('additional_phones', $phone)
                        ->orWhereJsonContains('additional_phones', $suffix))
                    ->limit(2)
                    ->get();

                if ($matches->count() > 1) {
                    continue;
                }

                if ($matches->count() === 1) {
                    $customer = $matches->first();
                    $knownSuffixes = collect([$customer->phone_number, ...($customer->additional_phones ?? [])])
                        ->map(fn (string $knownPhone) => substr(preg_replace('/\D+/', '', $knownPhone), -9));
                    $updates = ['is_guest' => false];
                    if ($customer->is_guest) {
                        $updates['full_name'] = $contact['name'];
                        if ($customer->bank_account_name === $customer->full_name) {
                            $updates['bank_account_name'] = $contact['name'];
                        }
                    }

                    if (! $knownSuffixes->contains($suffix)) {
                        $updates['additional_phones'] = array_values(array_unique([
                            ...($customer->additional_phones ?? []),
                            $phone,
                        ]));
                    }
                    $customer->update($updates);
                } else {
                    $customer = Customer::create([
                        'phone_number' => $phone,
                        'full_name' => $contact['name'],
                        'bank_account_name' => $contact['name'],
                        'status' => 'active',
                    ]);
                }
                $customer->wasRecentlyCreated ? $created++ : $updated++;
            }
        });

        return response()->json([
            'created' => $created,
            'updated' => $updated,
        ]);
    }
}
