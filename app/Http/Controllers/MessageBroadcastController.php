<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PhoneExclusion;
use App\Models\SmsOutbox;
use App\Services\CustomerMessageDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageBroadcastController extends Controller
{
    public function index()
    {
        $queueCounts = SmsOutbox::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
        $recentMessages = SmsOutbox::latest()->paginate(30);

        return view('messages.index', compact('queueCounts', 'recentMessages'));
    }

    public function store(
        Request $request,
        CustomerMessageDeliveryService $customerMessages,
    ) {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'confirmation' => 'required|accepted',
        ]);

        $excludedPhones = PhoneExclusion::query()
            ->pluck('phone_number')
            ->map(fn (string $phone) => $this->normalizeRecipientPhone($phone))
            ->filter()
            ->flip();
        $queued = 0;
        $failed = 0;
        $skipped = Customer::query()
            ->where('status', 'active')
            ->where('is_guest', true)
            ->count();
        $seenPhones = [];

        Customer::query()
            ->where('status', 'active')
            ->where('is_guest', false)
            ->select(['id', 'phone_number'])
            ->chunkById(500, function ($customers) use (
                &$queued,
                &$failed,
                &$skipped,
                &$seenPhones,
                $excludedPhones,
                $validated,
                $customerMessages,
            ) {
                $eligible = $customers->filter(function (Customer $customer) use (
                    &$skipped,
                    &$seenPhones,
                    $excludedPhones,
                ) {
                    $phone = $this->normalizeRecipientPhone($customer->phone_number);
                    if ($phone === null || $excludedPhones->has($phone) || isset($seenPhones[$phone])) {
                        $skipped++;

                        return false;
                    }

                    $seenPhones[$phone] = true;

                    return true;
                });

                foreach ($eligible as $customer) {
                    if ($customerMessages->send($customer->phone_number, $validated['message'])) {
                        $queued++;
                    } else {
                        $failed++;
                    }
                }
            });

        $response = back()->with(
            $failed > 0 ? 'error' : 'success',
            "تم إرسال الرسالة إلى {$queued} عميل؛ فشل الإرسال إلى {$failed} عميل، وتم تخطي {$skipped} رقم غير مسجل أو غير صالح أو مكرر أو مستثنى. لا يوجد إرسال SMS بديل."
        );

        return $response;
    }

    public function destroy(SmsOutbox $message)
    {
        $message->delete();

        return back()->with('success', 'تم حذف الرسالة من قائمة SMS بنجاح.');
    }

    private function normalizeRecipientPhone(mixed $phone): ?string
    {
        if (! is_string($phone)) {
            return null;
        }

        $digits = (string) preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '0')) {
            $digits = '967'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '967')) {
            $digits = '967'.$digits;
        }

        return preg_match('/^967\d{9}$/', $digits) === 1 ? $digits : null;
    }
}
