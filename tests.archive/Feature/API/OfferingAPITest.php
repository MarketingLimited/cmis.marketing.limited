<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Offering;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Offering (Products/Services) API Feature Tests
 */
class OfferingAPITest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_can_list_offerings_for_organization()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'product',
            'name' => 'Product 1',
            'price' => 50.00,
        ]);

        Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'service',
            'name' => 'Service 1',
            'price' => 100.00,
        ]);

        $response = $this->getJson("/api/offerings?org_id={$org->org_id}");

        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data')
                 ->assertJsonFragment(['name' => 'Product 1'])
                 ->assertJsonFragment(['name' => 'Service 1']);
    }

    #[Test]
    public function it_can_create_a_product()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $productData = [
            'org_id' => $org->org_id,
            'type' => 'product',
            'name' => 'Summer T-Shirt',
            'description' => 'Premium cotton t-shirt',
            'price' => 25.00,
            'currency' => 'BHD',
            'details' => [
                'features' => [
                    '100% cotton',
                    'Modern design',
                ],
                'benefits' => [
                    'Keeps you cool',
                ],
                'transformational_benefits' => [
                    'Boost confidence',
                ],
                'usps' => [
                    'Best price',
                ],
            ],
        ];

        $response = $this->postJson('/api/offerings', $productData);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Summer T-Shirt'])
                 ->assertJsonPath('data.type', 'product');

        $this->assertDatabaseHas('cmis.offerings', [
            'org_id' => $org->org_id,
            'name' => 'Summer T-Shirt',
            'type' => 'product',
        ]);
    }

    #[Test]
    public function it_can_create_a_service()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $serviceData = [
            'org_id' => $org->org_id,
            'type' => 'service',
            'name' => 'Marketing Consultation',
            'description' => 'Professional marketing consultation service',
            'price' => 150.00,
            'currency' => 'BHD',
        ];

        $response = $this->postJson('/api/offerings', $serviceData);

        $response->assertStatus(201)
                 ->assertJsonFragment(['type' => 'service']);
    }

    #[Test]
    public function it_can_view_a_specific_offering()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $offering = Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'product',
            'name' => 'Test Product',
            'price' => 75.00,
            'details' => [
                'features' => ['Feature 1', 'Feature 2'],
            ],
        ]);

        $response = $this->getJson("/api/offerings/{$offering->offering_id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Test Product'])
                 ->assertJsonPath('data.details.features.0', 'Feature 1');
    }

    #[Test]
    public function it_can_update_an_offering()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $offering = Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'product',
            'name' => 'Original Name',
            'price' => 50.00,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'price' => 60.00,
        ];

        $response = $this->putJson("/api/offerings/{$offering->offering_id}", $updateData);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Updated Name'])
                 ->assertJsonPath('data.price', 60.00);
    }

    #[Test]
    public function it_can_delete_an_offering()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $offering = Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'product',
            'name' => 'To Delete',
            'price' => 50.00,
        ]);

        $response = $this->deleteJson("/api/offerings/{$offering->offering_id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('cmis.offerings', [
            'offering_id' => $offering->offering_id,
        ]);
    }

    #[Test]
    public function it_can_filter_offerings_by_type()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'product',
            'name' => 'Product',
            'price' => 50.00,
        ]);

        Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'service',
            'name' => 'Service',
            'price' => 100.00,
        ]);

        $response = $this->getJson("/api/offerings?org_id={$org->org_id}&type=product");

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Product'])
                 ->assertJsonMissing(['name' => 'Service']);
    }

    #[Test]
    public function it_enforces_org_isolation_for_offerings()
    {
        $setup1 = $this->createUserWithOrg();
        $org1 = $setup1['org'];
        $user1 = $setup1['user'];

        $setup2 = $this->createUserWithOrg();
        $org2 = $setup2['org'];

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

        $this->actingAsUserInOrg($user1, $org1);

        $response = $this->getJson("/api/offerings?org_id={$org1->org_id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Org 1 Product'])
                 ->assertJsonMissing(['name' => 'Org 2 Product']);
    }

    #[Test]
    public function it_validates_offering_creation_data()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $invalidData = [
            'org_id' => $org->org_id,
            // Missing required fields: type, name, price
        ];

        $response = $this->postJson('/api/offerings', $invalidData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['type', 'name', 'price']);
    }

    #[Test]
    public function it_validates_price_is_positive()
    {
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);

        $invalidData = [
            'org_id' => $org->org_id,
            'type' => 'product',
            'name' => 'Invalid Price Product',
            'price' => -10.00, // Negative price
        ];

        $response = $this->postJson('/api/offerings', $invalidData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['price']);
    }

    #[Test]
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/offerings');

        $response->assertStatus(401);
    }
}
