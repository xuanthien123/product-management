<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Requests\Product\GetProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(GetProductRequest $request): JsonResponse
    {
        $productQuery = Product::query();

        $validated = $request->validated();

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

    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $product = Product::create($validated);

        return ApiResponse::success($product, 'Product created successfully.', 201);
    }

    public function show(Product $product): JsonResponse
    {
        return ApiResponse::success($product, 'Product retrieved successfully.');
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validated();

        $product->update($validated);

        return ApiResponse::success($product->refresh(), 'Product updated successfully.');
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return ApiResponse::success(null, 'Product deleted successfully.');
    }

    public function summary(): JsonResponse
    {
        $summary = Product::query()
            ->selectRaw(
                'COUNT(*) as total_products, COALESCE(SUM(quantity), 0) as total_units, COALESCE(SUM(price * quantity), 0) as inventory_value'
            )
            ->first();

        return ApiResponse::success([
            'total_products' => (int) $summary->total_products,
            'total_units' => (int) $summary->total_units,
            'inventory_value' => (float) $summary->inventory_value,
        ], 'Product summary retrieved successfully.');
    }
}
