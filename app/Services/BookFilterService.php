<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Database\Eloquent\Builder;

class BookFilterService
{
    /**
     * @param  Builder<Book>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Book>
     */
    public function apply(Builder $query, array $filters): Builder
    {
        if (isset($filters['category']) && $filters['category'] !== '') {
            $query->where('category_id', (int) $filters['category']);
        }

        return $query;
    }
}
