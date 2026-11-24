<?php

namespace App\Services;

use App\Exceptions\CategoryHasBooksException;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Throwable;

class CategoryService
{
    /**
     * @throws CategoryHasBooksException
     */
    public function delete(Category $category): void
    {
        DB::beginTransaction();

        try {
            if ($category->books()->exists()) {
                throw new CategoryHasBooksException;
            }

            $category->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }
}
