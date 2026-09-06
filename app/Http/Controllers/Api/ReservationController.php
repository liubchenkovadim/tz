<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Offer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function store(Offer $offer, StoreReservationRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($offer, $request) {
            /** @var Offer $lockedOffer */
            $lockedOffer = Offer::where('id', $offer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOffer->available_units <= 0) {
                return response()->json([
                    'message' => 'No available units remaining for this offer.',
                ], 422);
            }

            if ($lockedOffer->expires_at <= now()) {
                return response()->json([
                    'message' => 'This offer has expired.',
                ], 422);
            }

            $lockedOffer->decrement('available_units');

            $reservation = $lockedOffer->reservations()->create([
                'client_reference' => $request->client_reference,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
            ]);

            return (new ReservationResource($reservation))
                ->response()
                ->setStatusCode(201);
        });
    }
}
