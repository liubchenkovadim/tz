<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'city',
    ];

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function bestOffer(): HasOne
    {
        return $this->hasOne(Offer::class)->ofMany([
            'price' => 'min',
            'id' => 'min',
        ]);
    }
}
