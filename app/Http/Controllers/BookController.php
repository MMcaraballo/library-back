<?php

namespace App\Http\Controllers;

use App\Actions\Books\CreateBook;
use App\Actions\Books\DeleteBook;
use App\Actions\Books\ListBooks;
use App\Actions\Books\UpdateBook;
use App\Http\Requests\IndexBookRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookCollection;
use App\Http\Resources\BookResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BookController extends Controller
{
    public function index(IndexBookRequest $request, ListBooks $action): BookCollection
    {
        return new BookCollection($action->handle($request->validated()));
    }

    public function store(StoreBookRequest $request, CreateBook $action): JsonResponse
    {
        return (new BookResource($action->handle($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateBookRequest $request, int $id, UpdateBook $action): BookResource
    {
        return new BookResource($action->handle($id, $request->validated()));
    }

    public function destroy(int $id, DeleteBook $action): Response
    {
        $action->handle($id);

        return response()->noContent();
    }
}
