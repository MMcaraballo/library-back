<?php

namespace App\Actions\Books;

use App\Models\Book;
use Illuminate\Support\Facades\DB;
use Throwable;

final class UpdateBook
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(int $id, array $data): Book
    {
        DB::beginTransaction();

        try {
            $book = Book::query()->findOrFail($id);
            $book->update($data);
            $book->load('category');

            DB::commit();

            return $book;
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }
}
