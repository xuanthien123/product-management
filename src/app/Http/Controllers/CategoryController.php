<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(
            Category::query()->orderBy('name')->get(),
            'Categories retrieved successfully.'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
        ]);

        $category = Category::query()->create($validated);

        return ApiResponse::success($category, 'Category created successfully.', 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
        ]);

        $category->update($validated);

        return ApiResponse::success($category->refresh(), 'Category updated successfully.');
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return ApiResponse::success(null, 'Category deleted successfully.');
    }
}
