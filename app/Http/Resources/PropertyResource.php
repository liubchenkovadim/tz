<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $bestOffer = $this->bestOffer;

        return [
            'code' => $this->code,
            'name' => $this->name,
            'city' => $this->city,
            'best_offer' => $bestOffer ? [
                'id' => $bestOffer->id,
                'supplier' => $bestOffer->supplier?->slug ?? $bestOffer->supplier_id,
                'price' => $bestOffer->price,
                'currency' => $bestOffer->currency,
                'available_units' => $bestOffer->available_units,
                'expires_at' => $bestOffer->expires_at?->toIso8601String(),
            ] : null,
        ];
    }
}
