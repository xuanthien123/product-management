<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Requests\Product\GetProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Exports\ProductsExport;
use App\Imports\ProductsImport;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ProductController extends Controller
{
    public function index(GetProductRequest $request): JsonResponse
    {
        $productQuery = Product::query()->with('categories');

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
        if (isset($validated['category_id'])) {
            $productQuery->whereHas(
                'categories',
                fn ($categoryQuery) => $categoryQuery->whereKey($validated['category_id'])
            );
        }

        $summary = (clone $productQuery)
            ->selectRaw(
                'COALESCE(SUM(quantity), 0) as total_units, COALESCE(SUM(price * quantity), 0) as inventory_value'
            )
            ->first();

        if (
            ($validated['sort_by'] ?? null) === 'price'
            || (! isset($validated['sort_by']) && isset($validated['sort']))
        ) {
            $productQuery
                ->orderBy('price', $validated['sort'] ?? 'asc')
                ->orderByDesc('id');
        } else {
            $sortDirection = ($validated['sort_by'] ?? null) === 'created_at'
                ? ($validated['sort'] ?? 'desc')
                : 'desc';

            $productQuery
                ->orderBy('created_at', $sortDirection)
                ->orderByDesc('id');
        }

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
        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['category_ids']);

        $product = DB::transaction(function () use ($validated, $categoryIds) {
            $product = Product::create($validated);
            $product->categories()->sync($categoryIds);

            return $product;
        });

        return ApiResponse::success(
            $product->load('categories'),
            'Product created successfully.',
            201
        );
    }

    public function show(Product $product): JsonResponse
    {
        return ApiResponse::success(
            $product->load('categories'),
            'Product retrieved successfully.'
        );
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validated();
        $hasCategoryIds = array_key_exists('category_ids', $validated);
        $categoryIds = $validated['category_ids'] ?? null;
        unset($validated['category_ids']);

        DB::transaction(function () use ($product, $validated, $hasCategoryIds, $categoryIds) {
            $product->update($validated);

            if ($hasCategoryIds) {
                $product->categories()->sync($categoryIds);
            }
        });

        return ApiResponse::success(
            $product->refresh()->load('categories'),
            'Product updated successfully.'
        );
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

    public function export(GetProductRequest $request): BinaryFileResponse
    {
        return ExcelFacade::download(
            new ProductsExport($request->validated()),
            'products.xlsx'
        );
    }

    public function import(Request $request, Excel $excel): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsm,xlsx,xls,csv',
                'max:10240'
            ],
        ]);

        $file = $request->file('file');
        $reader = IOFactory::createReaderForFile($file->getRealPath());
        $worksheetNames = $reader->listWorksheetNames($file->getRealPath());
        $sheetName = in_array('Products', $worksheetNames, true)
            ? 'Products'
            : ($worksheetNames[0] ?? 'Worksheet');
        $import = new ProductsImport($sheetName);

        try {
            DB::transaction(function () use ($excel, $import, $file) {
                $excel->import($import, $file);
            });
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error(
                'Unable to import products.',
                500
            );
        }

        $failures = $import->failures()
            ->groupBy(fn ($failure) => $failure->row())
            ->map(function ($rowFailures, $row) {
                $values = $rowFailures->first()->values();

                return [
                    'row' => (int) $row,
                    'product' => [
                        'name' => $values['name'] ?? null,
                        'price' => $values['price'] ?? null,
                        'quantity' => $values['quantity'] ?? null,
                        'description' => $values['description'] ?? null,
                        'categories' => $values['categories'] ?? $values['category'] ?? null,
                    ],
                    'errors' => $rowFailures
                        ->flatMap(fn ($failure) => $failure->errors())
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        $importedCount = $import->importedCount();
        $failedRowCount = count($failures);

        return ApiResponse::success(
            null,
            $failures === []
                ? 'Import products successfully'
                : 'Import products completed with some invalid rows',
            200,
            [
                'imported' => $importedCount,
                'failed' => $failedRowCount,
                'imported_products' => $import->importedProducts(),
                'failures' => $failures,
            ]
        );
    }
}
