<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PhoneExclusion;
use App\Models\SmsOutbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcast_reports_unavailable_delivery_and_skips_excluded_numbers(): void
    {
        Customer::create([
            'full_name' => 'عميل نشط',
            'phone_number' => '777111111',
            'bank_account_name' => 'عميل نشط',
        ]);
        Customer::create([
            'full_name' => 'عميل مستثنى',
            'phone_number' => '777222222',
            'bank_account_name' => 'عميل مستثنى',
        ]);
        Customer::create([
            'full_name' => 'عميل موقوف',
            'phone_number' => '777333333',
            'bank_account_name' => 'عميل موقوف',
            'status' => 'inactive',
        ]);
        PhoneExclusion::create(['phone_number' => '777222222']);

        $this->actingAs(User::factory()->create())
            ->post(route('messages.broadcast'), [
                'message' => 'عرض خاص',
                'confirmation' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'فشل الإرسال إلى 1 عميل'));

        $this->assertDatabaseCount('sms_outbox', 0);
    }

    public function test_broadcast_only_targets_unique_registered_customers_with_valid_phones(): void
    {
        Customer::create([
            'full_name' => 'عميل مسجل 1',
            'phone_number' => '777444555',
            'bank_account_name' => 'عميل مسجل 1',
        ]);
        Customer::create([
            'full_name' => 'عميل مسجل مكرر',
            'phone_number' => '0777444555',
            'bank_account_name' => 'عميل مسجل مكرر',
        ]);
        Customer::create([
            'full_name' => 'عميل مسجل 2',
            'phone_number' => '777666777',
            'bank_account_name' => 'عميل مسجل 2',
        ]);
        Customer::create([
            'full_name' => 'رقم غير صالح',
            'phone_number' => '123',
            'bank_account_name' => 'رقم غير صالح',
        ]);
        foreach (range(1, 3) as $number) {
            Customer::create([
                'full_name' => "عملية بيع {$number}",
                'phone_number' => "77788899{$number}",
                'bank_account_name' => "عملية بيع {$number}",
                'is_guest' => true,
            ]);
        }
        $this->actingAs(User::factory()->create())
            ->post(route('messages.broadcast'), [
                'message' => 'عرض خاص',
                'confirmation' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'فشل الإرسال إلى 2 عميل')
                && str_contains($message, 'تم تخطي 5 رقم'));

        $this->assertDatabaseCount('sms_outbox', 0);
    }

    public function test_owner_can_delete_a_message_from_sms_queue_without_deleting_sale(): void
    {
        $message = SmsOutbox::create([
            'phone_number' => '777111111',
            'message' => 'رسالة للاختبار',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSee(route('messages.destroy', $message))
            ->assertSee('حذف');

        $this->actingAs(User::factory()->create())
            ->delete(route('messages.destroy', $message))
            ->assertRedirect()
            ->assertSessionHas('success', 'تم حذف الرسالة من قائمة SMS بنجاح.');

        $this->assertDatabaseMissing('sms_outbox', ['id' => $message->id]);
    }
}
