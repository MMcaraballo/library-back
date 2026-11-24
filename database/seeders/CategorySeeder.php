<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Novela', 'Ciencia', 'Historia', 'Tecnología', 'Poesía'] as $name) {
            Category::query()->firstOrCreate(['name' => $name]);
        }
    }
}
