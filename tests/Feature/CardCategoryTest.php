<?php

namespace Tests\Feature;

use App\Models\CardCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_be_created_with_optional_inventory_details_and_description(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('categories.store'), [
                'name' => 'فئة اختبار',
                'price' => 1500,
                'cost_price' => 900,
                'low_stock_threshold' => 7,
                'description' => 'سرعة وصلاحية الفئة',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('card_categories', [
            'name' => 'فئة اختبار',
            'price' => 1500,
            'cost_price' => 900,
            'low_stock_threshold' => 7,
            'description' => 'سرعة وصلاحية الفئة',
        ]);
    }

    public function test_category_can_be_created_without_optional_inventory_details_or_description(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('categories.store'), [
                'name' => 'فئة أساسية',
                'price' => 500,
            ])
            ->assertRedirect();

        $category = CardCategory::where('name', 'فئة أساسية')->firstOrFail();

        $this->assertEquals(0, $category->cost_price);
        $this->assertSame(5, $category->low_stock_threshold);
        $this->assertNull($category->description);
    }

    public function test_voucher_page_places_description_under_management_title(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('vouchers.index'))
            ->assertOk()
            ->assertSee('إدارة الكروت والفئات')
            ->assertSee('إضافة الفئات، رفع ملفات الميكروتك/اليوزر مانيجر، واستعراض الكروت المتاحة')
            ->assertSee('<div class="min-w-0">', false)
            ->assertSee('bg-slate-900 px-4 py-3', false)
            ->assertSee('text-amber-400', false)
            ->assertSee('قائمة الكروت المسجلة');
    }
}
