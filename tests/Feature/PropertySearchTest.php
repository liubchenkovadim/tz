<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertySearchTest extends TestCase
{
    use RefreshDatabase;

    protected Supplier $supplierA;
    protected Supplier $supplierB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supplierA = Supplier::create(['slug' => 'supplier-a', 'name' => 'Supplier A']);
        $this->supplierB = Supplier::create(['slug' => 'supplier-b', 'name' => 'Supplier B']);
    }

    public function test_property_search_returns_cheapest_valid_offer_per_property(): void
    {
        $property = Property::create([
            'code' => 'BCN-0001',
            'name' => 'Apartment near Sagrada Familia',
            'city' => 'Barcelona',
        ]);

        // Expensive offer from supplier-a
        Offer::create([
            'supplier_id' => $this->supplierA->id,
            'property_id' => $property->id,
            'external_id' => 'offer-a-1',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'max_guests' => 4,
            'price' => 90000,
            'currency' => 'EUR',
            'available_units' => 2,
            'expires_at' => now()->addDays(5),
        ]);

        // Cheaper offer from supplier-b (should be selected as best_offer)
        $cheaperOffer = Offer::create([
            'supplier_id' => $this->supplierB->id,
            'property_id' => $property->id,
            'external_id' => 'offer-b-1',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'max_guests' => 4,
            'price' => 72500,
            'currency' => 'EUR',
            'available_units' => 3,
            'expires_at' => now()->addDays(5),
        ]);

        $response = $this->getJson('/api/properties?city=Barcelona&check_in=2026-10-10&check_out=2026-10-15&guests=2');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    [
                        'code' => 'BCN-0001',
                        'name' => 'Apartment near Sagrada Familia',
                        'city' => 'Barcelona',
                        'best_offer' => [
                            'id' => $cheaperOffer->id,
                            'supplier' => 'supplier-b',
                            'price' => 72500,
                            'currency' => 'EUR',
                            'available_units' => 3,
                        ],
                    ],
                ],
            ]);
    }

    public function test_property_search_filters_out_invalid_offers(): void
    {
        $property = Property::create([
            'code' => 'BCN-0002',
            'name' => 'Gothic Quarter Studio',
            'city' => 'Barcelona',
        ]);

        // Expired offer
        Offer::create([
            'supplier_id' => $this->supplierA->id,
            'property_id' => $property->id,
            'external_id' => 'offer-exp',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'max_guests' => 4,
            'price' => 50000,
            'currency' => 'EUR',
            'available_units' => 2,
            'expires_at' => now()->subDay(),
        ]);

        // Unavailable units offer
        Offer::create([
            'supplier_id' => $this->supplierA->id,
            'property_id' => $property->id,
            'external_id' => 'offer-no-units',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'max_guests' => 4,
            'price' => 40000,
            'currency' => 'EUR',
            'available_units' => 0,
            'expires_at' => now()->addDays(5),
        ]);

        // Insufficient max_guests offer
        Offer::create([
            'supplier_id' => $this->supplierA->id,
            'property_id' => $property->id,
            'external_id' => 'offer-guests',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'max_guests' => 1,
            'price' => 30000,
            'currency' => 'EUR',
            'available_units' => 1,
            'expires_at' => now()->addDays(5),
        ]);

        $response = $this->getJson('/api/properties?city=Barcelona&check_in=2026-10-10&check_out=2026-10-15&guests=2');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_property_search_pagination(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $prop = Property::create([
                'code' => "PROP-00{$i}",
                'name' => "Property {$i}",
                'city' => 'Madrid',
            ]);

            Offer::create([
                'supplier_id' => $this->supplierA->id,
                'property_id' => $prop->id,
                'external_id' => "offer-mad-{$i}",
                'check_in' => '2026-10-10',
                'check_out' => '2026-10-15',
                'max_guests' => 2,
                'price' => 10000 * $i,
                'currency' => 'EUR',
                'available_units' => 1,
                'expires_at' => now()->addDays(5),
            ]);
        }

        $response = $this->getJson('/api/properties?city=Madrid&check_in=2026-10-10&check_out=2026-10-15&guests=2&per_page=2&page=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
                'next',
                'prev',
                'per_page',
            ]);

        $this->assertEquals('PROP-001', $response->json('data.0.code'));
        $this->assertEquals('PROP-002', $response->json('data.1.code'));
    }
}
