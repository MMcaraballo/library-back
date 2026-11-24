<?php

namespace App\Http\Controllers;

use App\Actions\Categories\ListCategories;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(ListCategories $action): AnonymousResourceCollection
    {
        return CategoryResource::collection($action->handle());
    }
}
