<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchPropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Offer;
use App\Models\Property;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PropertyController extends Controller
{
    public function index(SearchPropertyRequest $request): AnonymousResourceCollection
    {
        $checkIn = $request->check_in;
        $checkOut = $request->check_out;
        $guests = (int) $request->guests;
        $city = $request->city;
        $perPage = (int) ($request->per_page ?? 15);

        $validOfferFilter = function ($query) use ($checkIn, $checkOut, $guests) {
            $query->where('check_in', $checkIn)
                ->where('check_out', $checkOut)
                ->where('max_guests', '>=', $guests)
                ->where('available_units', '>', 0)
                ->where('expires_at', '>', now());
        };

        $properties = Property::query()
            ->when($city, fn ($q) => $q->where('city', $city))
            ->whereHas('offers', $validOfferFilter)
            ->addSelect([
                'min_price' => Offer::select('price')
                    ->whereColumn('offers.property_id', 'properties.id')
                    ->where('check_in', $checkIn)
                    ->where('check_out', $checkOut)
                    ->where('max_guests', '>=', $guests)
                    ->where('available_units', '>', 0)
                    ->where('expires_at', '>', now())
                    ->orderBy('price', 'asc')
                    ->limit(1),
            ])
            ->with(['bestOffer' => function ($hasOne) use ($validOfferFilter) {
                $hasOne->tap($validOfferFilter)->with('supplier');
            }])
            ->orderBy('min_price', 'asc')
            ->paginate($perPage);

        return PropertyResource::collection($properties)->additional([
            'next' => $properties->nextPageUrl(),
            'prev' => $properties->previousPageUrl(),
            'per_page' => $properties->perPage(),
        ]);
    }
}
