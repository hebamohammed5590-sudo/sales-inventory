<?php

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Enums\Role;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => Role::Admin,
            'is_active' => true,
        ]);
    }

    public function test_default_application_language_is_english(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route('dashboard')
        );

        $response->assertOk();

        $response->assertSee(
            'lang="en"',
            false
        );

        $response->assertSee(
            'dir="ltr"',
            false
        );
    }

    public function test_authenticated_user_can_switch_to_arabic(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('locale.update', [
                'locale' => 'ar',
            ])
        );

        $response->assertRedirect();

        $response->assertSessionHas(
            'locale',
            'ar'
        );
    }

    public function test_authenticated_user_can_switch_to_english(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->withSession([
                'locale' => 'ar',
            ])
            ->post(
                route('locale.update', [
                    'locale' => 'en',
                ])
            );

        $response->assertRedirect();

        $response->assertSessionHas(
            'locale',
            'en'
        );
    }

    public function test_guest_can_switch_application_language(): void
    {
        $response = $this->post(
            route('locale.update', [
                'locale' => 'ar',
            ])
        );

        $response->assertRedirect();

        $response->assertSessionHas(
            'locale',
            'ar'
        );
    }

    public function test_unsupported_language_returns_not_found(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('locale.update', [
                'locale' => 'fr',
            ])
        );

        $response->assertNotFound();
    }

    public function test_arabic_dashboard_uses_rtl_direction(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->withSession([
                'locale' => 'ar',
            ])
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $response->assertSee(
            'lang="ar"',
            false
        );

        $response->assertSee(
            'dir="rtl"',
            false
        );
    }

    public function test_english_dashboard_uses_ltr_direction(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->withSession([
                'locale' => 'en',
            ])
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $response->assertSee(
            'lang="en"',
            false
        );

        $response->assertSee(
            'dir="ltr"',
            false
        );
    }

    public function test_arabic_dashboard_displays_translated_heading(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->withSession([
                'locale' => 'ar',
            ])
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $response->assertSee(
            'لوحة التحكم'
        );
    }

    public function test_arabic_translation_file_contains_core_translations(): void
    {
        app()->setLocale(
            'ar'
        );

        $this->assertSame(
            'لوحة التحكم',
            __('Dashboard')
        );

        $this->assertSame(
            'المنتجات',
            __('Products')
        );

        $this->assertSame(
            'العملاء',
            __('Customers')
        );

        $this->assertSame(
            'الموردون',
            __('Suppliers')
        );

        $this->assertSame(
            'الإعدادات',
            __('Settings')
        );

        $this->assertSame(
            'العنوان',
            __('Address')
        );
    }

    public function test_invalid_session_locale_falls_back_to_english(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->withSession([
                'locale' => 'invalid',
            ])
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $response->assertSee(
            'lang="en"',
            false
        );

        $response->assertSee(
            'dir="ltr"',
            false
        );
    }

    public function test_navigation_displays_arabic_language_switcher(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->withSession([
                'locale' => 'en',
            ])
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $response->assertSee(
            'العربية'
        );

        $response->assertSee(
            route('locale.update', [
                'locale' => 'ar',
            ])
        );
    }

    public function test_navigation_displays_english_language_switcher(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->withSession([
                'locale' => 'ar',
            ])
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $response->assertSee(
            'English'
        );

        $response->assertSee(
            route('locale.update', [
                'locale' => 'en',
            ])
        );
    }

    public function test_arabic_login_page_uses_rtl_direction(): void
    {
        $response = $this
            ->withSession([
                'locale' => 'ar',
            ])
            ->get(
                route('login')
            );

        $response->assertOk();

        $response->assertSee(
            'lang="ar"',
            false
        );

        $response->assertSee(
            'dir="rtl"',
            false
        );
    }

    public function test_language_selection_persists_between_requests(): void
    {
        $this->actingAs(
            $this->admin
        );

        $this->post(
            route('locale.update', [
                'locale' => 'ar',
            ])
        )->assertSessionHas(
            'locale',
            'ar'
        );

        $response = $this->get(
            route('dashboard')
        );

        $response->assertOk();

        $response->assertSee(
            'lang="ar"',
            false
        );

        $response->assertSee(
            'لوحة التحكم'
        );
    }

    public function test_arabic_dashboard_displays_translated_metrics(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->withSession([
                'locale' => 'ar',
            ])
            ->get(route('dashboard'));

        $response->assertOk();

        $response->assertSee('مبيعات اليوم');

        $response->assertSee('المبيعات الشهرية');

        $response->assertSee('المنتجات منخفضة المخزون');
    }

    public function test_arabic_customer_create_page_displays_translated_fields(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->withSession([
                'locale' => 'ar',
            ])
            ->get(route('customers.create'));

        $response->assertOk();

        $response->assertSee('الاسم');

        $response->assertSee('الهاتف');

        $response->assertSee('العنوان');

        $response->assertSee('ملاحظات');
    }

    public function test_arabic_invoice_show_and_print_pages_are_translated(): void
    {
        $customer = Customer::factory()->create();

        $product = Product::factory()->create([
            'quantity' => 10,
        ]);

        $invoice = app(InvoiceService::class)->create(
            InvoiceType::Sale,
            $this->admin,
            [
                'customer_id' => $customer->id,

                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
            ]
        );

        $showResponse = $this
            ->actingAs($this->admin)
            ->withSession([
                'locale' => 'ar',
            ])
            ->get(route('invoices.show', [
                'type' => InvoiceType::Sale->value,
                'invoice' => $invoice,
            ]));

        $showResponse->assertOk();

        $showResponse->assertSee('بيانات الفاتورة');

        $showResponse->assertSee('العميل');

        $showResponse->assertSee('سعر الوحدة');

        $printResponse = $this
            ->withSession([
                'locale' => 'ar',
            ])
            ->get(route('invoices.print', [
                'type' => InvoiceType::Sale->value,
                'invoice' => $invoice,
            ]));

        $printResponse->assertOk();

        $printResponse->assertSee('طباعة الفاتورة');

        $printResponse->assertSee('بيانات العميل');

        $printResponse->assertSee('سعر الوحدة');
    }

    public function test_reviewed_pages_display_arabic_translations(): void
    {
        $translations = json_decode(
            file_get_contents(lang_path('ar.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $pages = [
            '/reports/profit' => 'Profit Report',
            '/settings' => 'Settings',
            '/stock-adjustments' => 'Stock Adjustments',
            '/products' => 'Import Products',
            '/dashboard' => 'View Products',
        ];

        foreach ($pages as $url => $translationKey) {
            $this->assertArrayHasKey(
                $translationKey,
                $translations
            );

            $this->assertNotSame(
                $translationKey,
                $translations[$translationKey]
            );

            $response = $this
                ->actingAs($this->admin)
                ->withSession([
                    'locale' => 'ar',
                ])
                ->get($url);

            $response->assertOk();

            $response->assertSeeText(
                $translations[$translationKey]
            );

            $response->assertDontSeeText(
                $translationKey
            );
        }
    }

    public function test_reviewed_pages_still_display_english_translations(): void
    {
        $pages = [
            '/reports/profit' => 'Profit Report',
            '/settings' => 'Settings',
            '/stock-adjustments' => 'Stock Adjustments',
            '/products' => 'Import Products',
            '/dashboard' => 'View Products',
        ];

        foreach ($pages as $url => $expectedText) {
            $response = $this
                ->actingAs($this->admin)
                ->withSession([
                    'locale' => 'en',
                ])
                ->get($url);

            $response->assertOk();

            $response->assertSeeText(
                $expectedText
            );
        }
    }

    public function test_latest_review_pages_display_remaining_arabic_translations(): void
    {
        $translations = json_decode(
            file_get_contents(lang_path('ar.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $pages = [
            '/dashboard' => [
                'Current Stock',
                'Type',
            ],
            '/products' => [
                'CSV File',
                'Import CSV',
                'Download Sample CSV',
            ],
            '/products/create' => [
                'Product Name',
                'Product Image',
                'Create Product',
            ],
            '/stock-adjustments' => [
                'Add Adjustment',
                'Quantity Change',
                'Reason',
            ],
            '/invoices/sale' => [
                'Add Invoice',
                'Invoice Number',
                'Status',
            ],
            '/invoices/purchase' => [
                'Add Invoice',
                'Invoice Number',
                'Status',
            ],
            '/invoices/sale/create' => [
                'Create Invoice',
                'Items',
                'Remove',
                'Discount Type',
            ],
        ];

        foreach ($pages as $url => $keys) {
            $response = $this
                ->actingAs($this->admin)
                ->withSession([
                    'locale' => 'ar',
                ])
                ->get($url);

            $response->assertOk();

            foreach ($keys as $key) {
                $this->assertArrayHasKey(
                    $key,
                    $translations
                );

                $this->assertNotSame(
                    $key,
                    $translations[$key]
                );

                $response->assertSeeText(
                    $translations[$key]
                );

                $response->assertDontSeeText(
                    $key
                );
            }
        }
    }

    public function test_latest_review_pages_keep_english_locale(): void
    {
        $pages = [
            '/dashboard' => [
                'Current Stock',
                'Type',
            ],
            '/products' => [
                'CSV File',
                'Import CSV',
                'Download Sample CSV',
            ],
            '/products/create' => [
                'Product Name',
                'Product Image',
                'Create Product',
            ],
            '/stock-adjustments' => [
                'Add Adjustment',
                'Quantity Change',
                'Reason',
            ],
            '/invoices/sale' => [
                'Add Invoice',
                'Invoice Number',
                'Status',
            ],
            '/invoices/purchase' => [
                'Add Invoice',
                'Invoice Number',
                'Status',
            ],
            '/invoices/sale/create' => [
                'Create Invoice',
                'Items',
                'Remove',
                'Discount Type',
            ],
        ];

        foreach ($pages as $url => $expectedTexts) {
            $response = $this
                ->actingAs($this->admin)
                ->withSession([
                    'locale' => 'en',
                ])
                ->get($url);

            $response->assertOk();

            foreach ($expectedTexts as $expectedText) {
                $response->assertSeeText(
                    $expectedText
                );
            }
        }
    }
}
