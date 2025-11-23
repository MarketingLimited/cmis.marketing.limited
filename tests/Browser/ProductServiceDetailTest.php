<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Core\Organization;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProductServiceDetailTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::factory()->create();
        $this->user = User::factory()->create([
            'active_org_id' => $this->org->id,
        ]);

        // Create sample product
        \DB::table('cmis.offerings')->insert([
            'id' => Str::uuid(),
            'org_id' => $this->org->id,
            'name' => 'Test Product',
            'type' => 'product',
            'description' => 'Test product description',
            'price' => 99.99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create sample service
        \DB::table('cmis.offerings')->insert([
            'id' => Str::uuid(),
            'org_id' => $this->org->id,
            'name' => 'Test Service',
            'type' => 'service',
            'description' => 'Test service description',
            'price' => 199.99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test user can view product details.
     */
    public function test_user_can_view_product_details(): void
    {
        $product = \DB::table('cmis.offerings')->where('type', 'product')->first();

        $this->browse(function (Browser $browser) use ($product) {
            $browser->loginAs($this->user)
                ->visit("/products/{$product->id}")
                ->pause(2000)
                ->assertSee($product->name)
                ->assertSee($product->description)
                ->assertPresent('[data-test="product-details"]');
        });
    }

    /**
     * Test product details show pricing.
     */
    public function test_product_details_show_pricing(): void
    {
        $product = \DB::table('cmis.offerings')->where('type', 'product')->first();

        $this->browse(function (Browser $browser) use ($product) {
            $browser->loginAs($this->user)
                ->visit("/products/{$product->id}")
                ->pause(2000)
                ->assertSee('$')
                ->assertPresent('[data-test="product-price"]');
        });
    }

    /**
     * Test product details show features.
     */
    public function test_product_details_show_features(): void
    {
        $product = \DB::table('cmis.offerings')->where('type', 'product')->first();

        $this->browse(function (Browser $browser) use ($product) {
            $browser->loginAs($this->user)
                ->visit("/products/{$product->id}")
                ->pause(2000)
                ->assertPresent('[data-test="product-features"]');
        });
    }

    /**
     * Test user can view service details.
     */
    public function test_user_can_view_service_details(): void
    {
        $service = \DB::table('cmis.offerings')->where('type', 'service')->first();

        $this->browse(function (Browser $browser) use ($service) {
            $browser->loginAs($this->user)
                ->visit("/services/{$service->id}")
                ->pause(2000)
                ->assertSee($service->name)
                ->assertSee($service->description)
                ->assertPresent('[data-test="service-details"]');
        });
    }

    /**
     * Test service details show pricing.
     */
    public function test_service_details_show_pricing(): void
    {
        $service = \DB::table('cmis.offerings')->where('type', 'service')->first();

        $this->browse(function (Browser $browser) use ($service) {
            $browser->loginAs($this->user)
                ->visit("/services/{$service->id}")
                ->pause(2000)
                ->assertSee('$')
                ->assertPresent('[data-test="service-price"]');
        });
    }

    /**
     * Test product page shows images.
     */
    public function test_product_page_shows_images(): void
    {
        $product = \DB::table('cmis.offerings')->where('type', 'product')->first();

        $this->browse(function (Browser $browser) use ($product) {
            $browser->loginAs($this->user)
                ->visit("/products/{$product->id}")
                ->pause(2000)
                ->assertPresent('[data-test="product-image"]');
        });
    }

    /**
     * Test user can add product to cart.
     */
    public function test_user_can_add_product_to_cart(): void
    {
        $product = \DB::table('cmis.offerings')->where('type', 'product')->first();

        $this->browse(function (Browser $browser) use ($product) {
            $browser->loginAs($this->user)
                ->visit("/products/{$product->id}")
                ->pause(2000)
                ->click('[data-test="add-to-cart"]')
                ->pause(2000)
                ->assertSee('added to cart');
        });
    }

    /**
     * Test user can request service quote.
     */
    public function test_user_can_request_service_quote(): void
    {
        $service = \DB::table('cmis.offerings')->where('type', 'service')->first();

        $this->browse(function (Browser $browser) use ($service) {
            $browser->loginAs($this->user)
                ->visit("/services/{$service->id}")
                ->pause(2000)
                ->click('[data-test="request-quote"]')
                ->pause(1000)
                ->assertPresent('[data-test="quote-form"]');
        });
    }

    /**
     * Test product page shows related products.
     */
    public function test_product_page_shows_related_products(): void
    {
        $product = \DB::table('cmis.offerings')->where('type', 'product')->first();

        $this->browse(function (Browser $browser) use ($product) {
            $browser->loginAs($this->user)
                ->visit("/products/{$product->id}")
                ->pause(2000)
                ->assertPresent('[data-test="related-products"]');
        });
    }

    /**
     * Test service page shows portfolio/case studies.
     */
    public function test_service_page_shows_portfolio(): void
    {
        $service = \DB::table('cmis.offerings')->where('type', 'service')->first();

        $this->browse(function (Browser $browser) use ($service) {
            $browser->loginAs($this->user)
                ->visit("/services/{$service->id}")
                ->pause(2000)
                ->assertPresent('[data-test="service-portfolio"]');
        });
    }

    /**
     * Test product page shows reviews/ratings.
     */
    public function test_product_page_shows_reviews(): void
    {
        $product = \DB::table('cmis.offerings')->where('type', 'product')->first();

        $this->browse(function (Browser $browser) use ($product) {
            $browser->loginAs($this->user)
                ->visit("/products/{$product->id}")
                ->pause(2000)
                ->assertPresent('[data-test="product-reviews"]');
        });
    }

    /**
     * Test user can share product/service.
     */
    public function test_user_can_share_offering(): void
    {
        $product = \DB::table('cmis.offerings')->where('type', 'product')->first();

        $this->browse(function (Browser $browser) use ($product) {
            $browser->loginAs($this->user)
                ->visit("/products/{$product->id}")
                ->pause(2000)
                ->click('[data-test="share-button"]')
                ->pause(1000)
                ->assertPresent('[data-test="share-options"]');
        });
    }
}
