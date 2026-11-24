<?php

namespace App\Actions\Books;

use App\Models\Book;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CreateBook
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Book
    {
        DB::beginTransaction();

        try {
            $book = Book::query()->create($data)->load('category');

            DB::commit();

            return $book;
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }
}
