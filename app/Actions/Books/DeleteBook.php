<?php

namespace App\Actions\Books;

use App\Models\Book;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DeleteBook
{
    public function handle(int $id): void
    {
        DB::beginTransaction();

        try {
            $book = Book::query()->findOrFail($id);
            $book->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }
}
