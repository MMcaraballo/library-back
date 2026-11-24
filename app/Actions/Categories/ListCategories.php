<?php

namespace App\Actions\Categories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ListCategories
{
    /**
     * @return Collection<int, Category>
     */
    public function handle(): Collection
    {
        DB::beginTransaction();

        try {
            $categories = Category::query()->orderBy('name')->get(['id', 'name']);

            DB::commit();

            return $categories;
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }
}
