<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements
    ToCollection,
    WithHeadingRow,
    WithValidation,
    WithChunkReading,
    WithMultipleSheets,
    SkipsOnFailure,
    SkipsEmptyRows
{
    use SkipsFailures;

    private int $importedCount = 0;

    /**
     * @var list<array{name: string, price: float, quantity: int, description: ?string, categories: string}>
     */
    private array $importedProducts = [];

    /**
     * @var array<string, array{id: int, name: string}>|null
     */
    private ?array $categoryMap = null;

    public function __construct(private string $sheetName)
    {
    }

    public function sheets(): array
    {
        return [$this->sheetName => $this];
    }

    public function collection(Collection $rows): void
    {
        $categoryProductRows = [];

        foreach ($rows as $row) {
            $categoryNames = $this->categoryNames($row['categories'] ?? null);
            $categories = $this->categoryMap();
            $categoryIds = array_map(
                fn (string $name) => $categories[Str::lower($name)]['id'],
                $categoryNames
            );
            $description = trim((string) ($row['description'] ?? ''));

            $product = Product::create([
                'name' => trim((string) $row['name']),
                'price' => (float) $row['price'],
                'quantity' => (int) $row['quantity'],
                'description' => $description !== '' ? $description : null,
            ]);

            foreach ($categoryIds as $categoryId) {
                $categoryProductRows[] = [
                    'category_id' => $categoryId,
                    'product_id' => $product->id,
                ];
            }

            $this->importedCount++;
            $this->importedProducts[] = [
                'name' => $product->name,
                'price' => (float) $product->price,
                'quantity' => $product->quantity,
                'description' => $product->description,
                'categories' => implode(' | ', array_map(
                    fn (string $name) => $categories[Str::lower($name)]['name'],
                    $categoryNames
                )),
            ];
        }

        if ($categoryProductRows !== []) {
            DB::table('category_product')->insert($categoryProductRows);
        }
    }

    public function rules(): array
    {
        return [
            '*.name' => ['required', 'string', 'max:255'],
            '*.price' => ['required', 'numeric', 'min:0'],
            '*.quantity' => ['required', 'integer', 'min:0'],
            '*.description' => ['nullable', 'string'],
            '*.categories' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $categories = $this->categoryMap();
                    $unknownNames = array_filter(
                        $this->categoryNames($value),
                        fn (string $name) => ! isset($categories[Str::lower($name)])
                    );

                    if ($unknownNames !== []) {
                        $fail('The selected categories do not exist: ' . implode(', ', $unknownNames) . '.');
                    }
                },
            ],
        ];
    }

    public function prepareForValidation(array $row, int $index): array
    {
        if (! array_key_exists('categories', $row) && array_key_exists('category', $row)) {
            $row['categories'] = $row['category'];
        }

        return $row;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }

    /**
     * @return list<array{name: string, price: float, quantity: int, description: ?string, categories: string}>
     */
    public function importedProducts(): array
    {
        return $this->importedProducts;
    }

    /**
     * @return list<string>
     */
    private function categoryNames(mixed $value): array
    {
        if (! is_string($value)) {
            return [];
        }

        $names = [];
        $seen = [];

        foreach (array_map('trim', explode('|', $value)) as $name) {
            $key = Str::lower($name);

            if ($name !== '' && ! isset($seen[$key])) {
                $names[] = $name;
                $seen[$key] = true;
            }
        }

        return $names;
    }

    /**
     * @return array<string, array{id: int, name: string}>
     */
    private function categoryMap(): array
    {
        if ($this->categoryMap === null) {
            $this->categoryMap = Category::query()
                ->get(['id', 'name'])
                ->mapWithKeys(fn (Category $category) => [
                    Str::lower($category->name) => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ],
                ])
                ->all();
        }

        return $this->categoryMap;
    }
}
