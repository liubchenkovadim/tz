<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supplier = Supplier::create([
            'slug' => 'supplier-a',
            'name' => 'Supplier A',
        ]);
    }

    public function test_can_submit_import_and_receive_202_accepted(): void
    {
        Queue::fake();

        $payload = [
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-2026-09-01-001',
            'sent_at' => '2026-09-01T10:00:00Z',
            'offers' => [
                [
                    'external_id' => 'offer-a-10001',
                    'property' => [
                        'code' => 'BCN-0001',
                        'name' => 'Apartment near Sagrada Familia',
                        'city' => 'Barcelona',
                    ],
                    'check_in' => '2026-10-10',
                    'check_out' => '2026-10-15',
                    'max_guests' => 4,
                    'price' => 72500,
                    'currency' => 'EUR',
                    'available_units' => 2,
                    'expires_at' => '2026-12-10T23:59:59Z',
                ],
            ],
        ];

        $response = $this->postJson('/api/imports', $payload);

        $response->assertStatus(202)
            ->assertJson([
                'data' => [
                    'status' => 'pending',
                ],
            ]);

        $this->assertDatabaseHas('imports', [
            'supplier_id' => $this->supplier->id,
            'external_import_id' => 'import-2026-09-01-001',
            'status' => 'pending',
            'total_offers' => 1,
        ]);

        Queue::assertPushed(ProcessImportJob::class);
    }

    public function test_import_submission_is_idempotent(): void
    {
        Queue::fake();

        $payload = [
            'supplier' => 'supplier-a',
            'external_import_id' => 'import-2026-09-01-001',
            'sent_at' => '2026-09-01T10:00:00Z',
            'offers' => [
                [
                    'external_id' => 'offer-a-10001',
                    'property' => [
                        'code' => 'BCN-0001',
                        'name' => 'Apartment near Sagrada Familia',
                        'city' => 'Barcelona',
                    ],
                    'check_in' => '2026-10-10',
                    'check_out' => '2026-10-15',
                    'max_guests' => 4,
                    'price' => 72500,
                    'currency' => 'EUR',
                    'available_units' => 2,
                    'expires_at' => '2026-12-10T23:59:59Z',
                ],
            ],
        ];

        $res1 = $this->postJson('/api/imports', $payload);
        $res1->assertStatus(202);

        $res2 = $this->postJson('/api/imports', $payload);
        $res2->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $res1->json('data.id'),
                    'status' => 'pending',
                ],
            ]);

        $this->assertDatabaseCount('imports', 1);
        Queue::assertPushed(ProcessImportJob::class, 1);
    }

    public function test_process_import_job_upserts_properties_and_offers(): void
    {
        $import = Import::create([
            'supplier_id' => $this->supplier->id,
            'external_import_id' => 'import-001',
            'sent_at' => now(),
            'status' => 'pending',
            'total_offers' => 1,
            'processed_offers' => 0,
            'payload' => [
                [
                    'external_id' => 'offer-a-10001',
                    'property' => [
                        'code' => 'BCN-0001',
                        'name' => 'Apartment near Sagrada Familia',
                        'city' => 'Barcelona',
                    ],
                    'check_in' => '2026-10-10',
                    'check_out' => '2026-10-15',
                    'max_guests' => 4,
                    'price' => 72500,
                    'currency' => 'EUR',
                    'available_units' => 2,
                    'expires_at' => '2026-12-10T23:59:59Z',
                ],
            ],
        ]);

        (new ProcessImportJob($import->id))->handle();

        $import->refresh();
        $this->assertEquals('completed', $import->status);
        $this->assertEquals(1, $import->processed_offers);
        $this->assertNotNull($import->completed_at);

        $this->assertDatabaseHas('properties', [
            'code' => 'BCN-0001',
            'city' => 'Barcelona',
        ]);

        $this->assertDatabaseHas('offers', [
            'supplier_id' => $this->supplier->id,
            'external_id' => 'offer-a-10001',
            'price' => 72500,
        ]);

        // Second import with updated offer data for the same external_id
        $import2 = Import::create([
            'supplier_id' => $this->supplier->id,
            'external_import_id' => 'import-002',
            'sent_at' => now(),
            'status' => 'pending',
            'total_offers' => 1,
            'processed_offers' => 0,
            'payload' => [
                [
                    'external_id' => 'offer-a-10001',
                    'property' => [
                        'code' => 'BCN-0001',
                        'name' => 'Apartment near Sagrada Familia Updated',
                        'city' => 'Barcelona',
                    ],
                    'check_in' => '2026-10-10',
                    'check_out' => '2026-10-15',
                    'max_guests' => 4,
                    'price' => 65000,
                    'currency' => 'EUR',
                    'available_units' => 5,
                    'expires_at' => '2026-12-10T23:59:59Z',
                ],
            ],
        ]);

        (new ProcessImportJob($import2->id))->handle();

        $this->assertDatabaseCount('offers', 1);
        $this->assertDatabaseHas('offers', [
            'supplier_id' => $this->supplier->id,
            'external_id' => 'offer-a-10001',
            'price' => 65000,
            'available_units' => 5,
        ]);
    }

    public function test_can_view_import_status(): void
    {
        $import = Import::create([
            'supplier_id' => $this->supplier->id,
            'external_import_id' => 'import-001',
            'sent_at' => '2026-09-01T10:00:00Z',
            'status' => 'completed',
            'total_offers' => 5,
            'processed_offers' => 5,
            'completed_at' => '2026-09-01T10:00:05Z',
        ]);

        $response = $this->getJson("/api/imports/{$import->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $import->id,
                    'supplier' => 'supplier-a',
                    'external_import_id' => 'import-001',
                    'status' => 'completed',
                    'total_offers' => 5,
                    'processed_offers' => 5,
                    'error' => null,
                ],
            ]);
    }
}
