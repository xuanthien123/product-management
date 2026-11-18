<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $productQuery = Product::query();

        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'min_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'sort' => ['sometimes', 'in:asc,desc'],
        ]);

        if (! empty($validated['search'])) {
            $productQuery->where(
                'name',
                'LIKE',
                '%' . $validated['search'] . '%'
            );
        }
        if (isset($validated['min_price'])) {
            $productQuery->where(
                'price',
                '>=',
                $validated['min_price']
            );
        }
        if (isset($validated['max_price'])) {
            $productQuery->where(
                'price',
                '<=',
                $validated['max_price']
            );
        }

        $summary = (clone $productQuery)
            ->selectRaw(
                'COALESCE(SUM(quantity), 0) as total_units, COALESCE(SUM(price * quantity), 0) as inventory_value'
            )
            ->first();

        $productQuery->orderBy(
            'price',
            $validated['sort'] ?? 'asc'
        )->orderBy('id');

        $products = $productQuery
            ->paginate($validated['per_page'] ?? 5)
            ->withQueryString();

        return ApiResponse::success(
            $products,
            'Products retrieved successfully.',
            200,
            [
                'total_units' => (int) $summary->total_units,
                'inventory_value' => (float) $summary->inventory_value,
            ],
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $product = Product::create($validated);

        return ApiResponse::success($product, 'Product created successfully.', 201);
    }

    public function show(Product $product): JsonResponse
    {
        return ApiResponse::success($product, 'Product retrieved successfully.');
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'quantity' => ['sometimes', 'required', 'integer', 'min:0'],
        ]);

        $product->update($validated);

        return ApiResponse::success($product->refresh(), 'Product updated successfully.');
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return ApiResponse::success(null, 'Product deleted successfully.');
    }
}
