<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        Supplier::firstOrCreate(
            ['slug' => 'supplier-a'],
            ['name' => 'Supplier A']
        );

        Supplier::firstOrCreate(
            ['slug' => 'supplier-b'],
            ['name' => 'Supplier B']
        );
    }
}
