<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImportRequest;
use App\Http\Resources\ImportResource;
use App\Jobs\ProcessImportJob;
use App\Models\Import;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;

class ImportController extends Controller
{
    public function store(StoreImportRequest $request): JsonResponse
    {
        $supplier = Supplier::where('slug', $request->supplier)->firstOrFail();

        $existingImport = Import::where('supplier_id', $supplier->id)
            ->where('external_import_id', $request->external_import_id)
            ->first();

        if ($existingImport) {
            return response()->json([
                'data' => [
                    'id' => $existingImport->id,
                    'status' => $existingImport->status,
                ],
            ], 200);
        }

        $import = Import::create([
            'supplier_id' => $supplier->id,
            'external_import_id' => $request->external_import_id,
            'sent_at' => $request->sent_at,
            'status' => 'pending',
            'total_offers' => count($request->offers),
            'processed_offers' => 0,
            'payload' => $request->offers,
        ]);

        ProcessImportJob::dispatch($import->id);

        return response()->json([
            'data' => [
                'id' => $import->id,
                'status' => $import->status,
            ],
        ], 202);
    }

    public function show(Import $import): ImportResource
    {
        return new ImportResource($import->load('supplier'));
    }
}
