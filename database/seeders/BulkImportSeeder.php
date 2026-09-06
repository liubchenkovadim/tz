<?php

namespace Database\Seeders;

use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class BulkImportSeeder extends Seeder
{
    public function run(): void
    {
        $supplierA = Supplier::firstOrCreate(['slug' => 'supplier-a'], ['name' => 'Supplier A']);
        $supplierB = Supplier::firstOrCreate(['slug' => 'supplier-b'], ['name' => 'Supplier B']);

        $cities = ['Barcelona', 'Madrid', 'Valencia', 'Seville', 'Malaga'];

        $offersA = [];
        $offersB = [];

        for ($i = 1; $i <= 50; $i++) {
            $code = sprintf('PROP-%04d', $i);
            $city = $cities[($i - 1) % count($cities)];

            $offersA[] = [
                'external_id' => "offer-a-{$i}",
                'property' => [
                    'code' => $code,
                    'name' => "Apartment {$i} in {$city}",
                    'city' => $city,
                ],
                'check_in' => '2026-10-10',
                'check_out' => '2026-10-15',
                'max_guests' => 4,
                'price' => 50000 + ($i * 1000),
                'currency' => 'EUR',
                'available_units' => 5,
                'expires_at' => '2026-12-31T23:59:59Z',
            ];

            // Supplier B has slightly lower prices for odd items and higher for even items
            $priceB = ($i % 2 === 1) ? (50000 + ($i * 1000) - 3000) : (50000 + ($i * 1000) + 2000);

            $offersB[] = [
                'external_id' => "offer-b-{$i}",
                'property' => [
                    'code' => $code,
                    'name' => "Apartment {$i} in {$city}",
                    'city' => $city,
                ],
                'check_in' => '2026-10-10',
                'check_out' => '2026-10-15',
                'max_guests' => 4,
                'price' => max(10000, $priceB),
                'currency' => 'EUR',
                'available_units' => 3,
                'expires_at' => '2026-12-31T23:59:59Z',
            ];
        }

        // Create import for Supplier A
        $importA = Import::create([
            'supplier_id' => $supplierA->id,
            'external_import_id' => 'bulk-import-supplier-a-001',
            'sent_at' => now(),
            'status' => 'pending',
            'total_offers' => count($offersA),
            'processed_offers' => 0,
            'payload' => $offersA,
        ]);

        (new ProcessImportJob($importA->id))->handle();

        // Create import for Supplier B
        $importB = Import::create([
            'supplier_id' => $supplierB->id,
            'external_import_id' => 'bulk-import-supplier-b-001',
            'sent_at' => now(),
            'status' => 'pending',
            'total_offers' => count($offersB),
            'processed_offers' => 0,
            'payload' => $offersB,
        ]);

        (new ProcessImportJob($importB->id))->handle();
    }
}
