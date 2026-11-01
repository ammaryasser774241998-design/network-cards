<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CustomerMessageDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerMessageDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_message_delivery_fails_explicitly_without_a_provider(): void
    {
        Http::fake();

        $this->assertFalse(app(CustomerMessageDeliveryService::class)->send(
            '967777123456',
            'رسالة للعميل',
        ));

        Http::assertNothingSent();
        $this->assertDatabaseCount('sms_outbox', 0);
    }

    public function test_removed_whatsapp_webhook_is_not_available(): void
    {
        $this->getJson('/api/whatsapp/webhook')->assertNotFound();
        $this->postJson('/api/whatsapp/webhook')->assertNotFound();
    }

    public function test_settings_page_loads_without_messaging_provider_configuration(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('لم يتم إعداد مزود رسائل صادر حالياً');
    }
}
