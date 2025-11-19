<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Core\Org;
use App\Models\Offering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

/**
 * Offering (Product/Service) Model Unit Tests
 */
class OfferingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_a_product()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $product = Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'product',
            'name' => 'Summer T-Shirt',
            'description' => 'Premium cotton t-shirt',
            'price' => 25.00,
            'currency' => 'BHD',
        ]);

        $this->assertDatabaseHas('cmis.offerings', [
            'offering_id' => $product->offering_id,
            'type' => 'product',
            'name' => 'Summer T-Shirt',
        ]);
    }

    /** @test */
    public function it_can_create_a_service()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $service = Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'service',
            'name' => 'Marketing Consultation',
            'description' => 'Professional marketing consultation',
            'price' => 100.00,
            'currency' => 'BHD',
        ]);

        $this->assertDatabaseHas('cmis.offerings', [
            'offering_id' => $service->offering_id,
            'type' => 'service',
        ]);
    }

    /** @test */
    public function it_belongs_to_an_organization()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $offering = Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'product',
            'name' => 'Test Product',
            'price' => 50.00,
        ]);

        $this->assertEquals($org->org_id, $offering->org->org_id);
    }

    /** @test */
    public function it_stores_features_benefits_and_usps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $offering = Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'product',
            'name' => 'Premium Product',
            'price' => 75.00,
            'details' => [
                'features' => [
                    'High quality materials',
                    'Modern design',
                    'Multiple colors',
                ],
                'benefits' => [
                    'Keeps you cool',
                    'Easy to wash',
                ],
                'transformational_benefits' => [
                    'Boost confidence',
                    'Professional appearance',
                ],
                'usps' => [
                    'Best price in market',
                    'Quality guarantee',
                ],
            ],
        ]);

        $this->assertCount(3, $offering->details['features']);
        $this->assertCount(2, $offering->details['benefits']);
        $this->assertCount(2, $offering->details['transformational_benefits']);
        $this->assertCount(2, $offering->details['usps']);
    }

    /** @test */
    public function it_validates_offering_type()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $offering = Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'product',
            'name' => 'Type Test',
            'price' => 50.00,
        ]);

        $this->assertContains($offering->type, ['product', 'service']);
    }

    /** @test */
    public function it_validates_price_format()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $offering = Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'product',
            'name' => 'Price Test',
            'price' => 99.99,
            'currency' => 'BHD',
        ]);

        $this->assertIsNumeric($offering->price);
        $this->assertGreaterThan(0, $offering->price);
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $offering = Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'product',
            'name' => 'UUID Test',
            'price' => 50.00,
        ]);

        $this->assertTrue(Str::isUuid($offering->offering_id));
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $offering = Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'product',
            'name' => 'Soft Delete Test',
            'price' => 50.00,
        ]);

        $offering->delete();

        $this->assertSoftDeleted('cmis.offerings', [
            'offering_id' => $offering->offering_id,
        ]);
    }

    /** @test */
    public function it_has_timestamps()
    {
        $org = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Test Org',
        ]);

        $offering = Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'product',
            'name' => 'Timestamp Test',
            'price' => 50.00,
        ]);

        $this->assertNotNull($offering->created_at);
        $this->assertNotNull($offering->updated_at);
    }

    /** @test */
    public function it_respects_rls_policies()
    {
        $org1 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 1',
        ]);

        $org2 = Org::create([
            'org_id' => Str::uuid(),
            'name' => 'Org 2',
        ]);

        Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org1->org_id,
            'type' => 'product',
            'name' => 'Org 1 Product',
            'price' => 50.00,
        ]);

        Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org2->org_id,
            'type' => 'product',
            'name' => 'Org 2 Product',
            'price' => 60.00,
        ]);

        $org1Products = Offering::where('org_id', $org1->org_id)->get();
        $this->assertCount(1, $org1Products);
        $this->assertEquals('Org 1 Product', $org1Products->first()->name);
    }
}
