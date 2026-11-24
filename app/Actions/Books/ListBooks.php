<?php

namespace App\Actions\Books;

use App\Models\Book;
use App\Services\BookFilterService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ListBooks
{
    public function __construct(private readonly BookFilterService $filters) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function handle(array $filters = []): LengthAwarePaginator
    {
        DB::beginTransaction();

        try {
            $books = $this->filters
                ->apply(Book::query()->with('category'), $filters)
                ->orderBy('id')
                ->paginate(self::perPage());

            DB::commit();

            return $books;
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Tamaño de página fijo del listado (no configurable por el cliente).
     */
    public static function perPage(): int
    {
        return 50;
    }
}
