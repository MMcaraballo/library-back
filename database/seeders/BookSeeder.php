<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        Category::query()->get()->each(function (Category $category): void {
            Book::factory()->count(3)->create(['category_id' => $category->id]);
        });
    }
}
