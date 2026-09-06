<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\Offer;
use App\Models\Property;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessImportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $importId)
    {
    }

    public function handle(): void
    {
        $import = Import::find($this->importId);

        if (!$import || $import->status === 'completed') {
            return;
        }

        $import->update([
            'status' => 'processing',
            'error' => null,
        ]);

        try {
            $offers = $import->payload ?? [];
            $processedCount = 0;

            foreach ($offers as $offerData) {
                DB::transaction(function () use ($import, $offerData) {
                    $property = Property::updateOrCreate(
                        ['code' => $offerData['property']['code']],
                        [
                            'name' => $offerData['property']['name'],
                            'city' => $offerData['property']['city'],
                        ]
                    );

                    Offer::updateOrCreate(
                        [
                            'supplier_id' => $import->supplier_id,
                            'external_id' => $offerData['external_id'],
                        ],
                        [
                            'property_id' => $property->id,
                            'check_in' => $offerData['check_in'],
                            'check_out' => $offerData['check_out'],
                            'max_guests' => $offerData['max_guests'],
                            'price' => $offerData['price'],
                            'currency' => $offerData['currency'],
                            'available_units' => $offerData['available_units'],
                            'expires_at' => $offerData['expires_at'],
                        ]
                    );
                });

                $processedCount++;
                $import->update(['processed_offers' => $processedCount]);
            }

            $import->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } catch (Throwable $e) {
            $import->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
