<?php

namespace Modules\Core\Category\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Core\Category\DTOs\CategoryDTO;
use Modules\Core\Category\Http\Requests\StoreCategoryRequest;
use Modules\Core\Category\Http\Requests\UpdateCategoryRequest;
use Modules\Core\Category\Http\Resources\CategoryResource;
use Modules\Core\Category\Services\CategoryService;

class CategoryController extends Controller
{
    public function __construct(protected readonly CategoryService $categoryService)
    {
        $this->applyPermissions(
            'categories',
            ['store', 'update', 'destroy']
        );
    }

    public function indexPublic()
    {
        $categories = $this->categoryService->all();

        return $this->paginatedResponse(CategoryResource::collection($categories));
    }

    public function showPublic($id)
    {
        $category = $this->categoryService->find($id);

        return $this->successResponse(CategoryResource::make($category));
    }

    public function index()
    {
        $categories = $this->categoryService->all();

        return $this->paginatedResponse(CategoryResource::collection($categories));
    }

    public function show($id)
    {
        $category = $this->categoryService->find($id);

        return $this->successResponse(CategoryResource::make($category));
    }

    public function store(StoreCategoryRequest $request)
    {
        $dto = CategoryDTO::fromRequest($request->validated());
        $category = $this->categoryService->store($dto);

        return $this->successResponse(CategoryResource::make($category))->created('category');
    }

    public function update(UpdateCategoryRequest $request, $id)
    {
        $dto = CategoryDTO::fromRequest($request->validated());
        $category = $this->categoryService->update($id, $dto);

        return $this->successResponse(CategoryResource::make($category))->updated('category');
    }

    public function destroy($id)
    {
        $this->categoryService->destroy($id);

        return $this->successResponse()->deleted('category');
    }
}
