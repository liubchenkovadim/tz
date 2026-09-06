<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    protected Supplier $supplier;
    protected Property $property;
    protected Offer $offer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supplier = Supplier::create(['slug' => 'supplier-a', 'name' => 'Supplier A']);
        $this->property = Property::create(['code' => 'BCN-0001', 'name' => 'Apartment near Sagrada Familia', 'city' => 'Barcelona']);

        $this->offer = Offer::create([
            'supplier_id' => $this->supplier->id,
            'property_id' => $this->property->id,
            'external_id' => 'offer-a-10001',
            'check_in' => '2026-10-10',
            'check_out' => '2026-10-15',
            'max_guests' => 4,
            'price' => 72500,
            'currency' => 'EUR',
            'available_units' => 2,
            'expires_at' => now()->addDays(5),
        ]);
    }

    public function test_can_reserve_available_offer(): void
    {
        $payload = [
            'client_reference' => 'web-order-9f782b1c',
            'customer_name' => 'John Smith',
            'customer_email' => 'john@example.com',
        ];

        $response = $this->postJson("/api/offers/{$this->offer->id}/reservations", $payload);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'offer_id' => $this->offer->id,
                    'client_reference' => 'web-order-9f782b1c',
                    'customer_name' => 'John Smith',
                    'customer_email' => 'john@example.com',
                ],
            ]);

        $this->assertDatabaseHas('reservations', [
            'offer_id' => $this->offer->id,
            'client_reference' => 'web-order-9f782b1c',
        ]);

        $this->offer->refresh();
        $this->assertEquals(1, $this->offer->available_units);
    }

    public function test_cannot_reserve_when_no_units_available(): void
    {
        $this->offer->update(['available_units' => 0]);

        $payload = [
            'client_reference' => 'web-order-9f782b1c',
            'customer_name' => 'John Smith',
            'customer_email' => 'john@example.com',
        ];

        $response = $this->postJson("/api/offers/{$this->offer->id}/reservations", $payload);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'No available units remaining for this offer.',
            ]);
    }

    public function test_cannot_reserve_expired_offer(): void
    {
        $this->offer->update(['expires_at' => now()->subHour()]);

        $payload = [
            'client_reference' => 'web-order-9f782b1c',
            'customer_name' => 'John Smith',
            'customer_email' => 'john@example.com',
        ];

        $response = $this->postJson("/api/offers/{$this->offer->id}/reservations", $payload);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'This offer has expired.',
            ]);
    }
}
